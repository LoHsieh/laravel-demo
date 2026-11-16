<?php

namespace App\Ordering;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Payment\InvalidPaymentNotification;
use App\Payment\PaymentNotification;
use Illuminate\Support\Facades\DB;

/**
 * 依付款通知記錄付款結果。付款結果以付款通知為準，顧客導回網站時帶的參數不改變訂單。
 *
 * - 同一筆通知重複送達（金流服務的交易編號已處理過，或這次付款嘗試已有結果）時不再處理。
 * - 付款成功且訂單待付款、付款時間在付款期限前 → 已付款。
 * - 付款失敗 → 訂單維持待付款，顧客可在付款期限內重新付款。
 * - 已取消的訂單收到付款成功 → 付款異常，由店家人工處理，不會出貨。
 * - 已逾期但排程尚未處理的訂單收到付款成功 → 先照付款逾期處理（歸還庫存保留、放回購物車），再變成付款異常。
 */
final class RecordPaymentResult
{
    public function __construct(private readonly ExpireOrder $expireOrder) {}

    /**
     * @throws InvalidPaymentNotification 找不到這次付款嘗試，或金額與付款嘗試不符
     */
    public function __invoke(PaymentNotification $notification): void
    {
        // 訂單的顧客不會改變，在交易外先查出來，交易中才能把鎖定顧客放在第一個查詢：
        // REPEATABLE READ 的快照在第一次一般讀取時建立，付款逾期放回購物車（ExpireOrder）必須讀到
        // 拿到顧客鎖之後的購物車與庫存，而不是等鎖之前的舊快照（與 PlaceOrder 相同的理由）。
        $customerId = Order::whereKey(
            Payment::where('merchant_trade_no', $notification->merchantTradeNo)->value('order_id'),
        )->value('customer_id');

        if ($customerId === null) {
            throw new InvalidPaymentNotification("找不到交易編號 {$notification->merchantTradeNo} 的付款。");
        }

        DB::transaction(function () use ($notification, $customerId) {
            // 鎖定順序一律是 顧客 → 付款 → 訂單 → 規格，與 ExpireOrder、PlaceOrder 一致，避免死結。
            Customer::whereKey($customerId)->lockForUpdate()->first();

            // 鎖定付款嘗試，同一筆通知同時送達兩次時，後到的會等前一筆完成，再讀到已記錄的結果。
            $payment = Payment::where('merchant_trade_no', $notification->merchantTradeNo)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status !== PaymentStatus::Pending
                || Payment::where('gateway_trade_no', $notification->gatewayTradeNo)->exists()) {
                return;
            }

            if ($notification->amount !== $payment->amount) {
                throw new InvalidPaymentNotification("交易編號 {$notification->merchantTradeNo} 的付款金額不符。");
            }

            $payment->update([
                'gateway_trade_no' => $notification->gatewayTradeNo,
                'status' => $notification->succeeded ? PaymentStatus::Succeeded : PaymentStatus::Failed,
                'result_message' => $notification->message,
                'simulated' => $notification->simulated,
                'paid_at' => $notification->paidAt,
            ]);

            if (! $notification->succeeded) {
                return;
            }

            // 鎖定並重新讀取訂單：排程或顧客取消同時改這筆訂單時，等對方完成後讀到最新狀態，
            // 對方也會等這裡完成，因此下面的狀態轉換不會被搶先。
            $order = Order::whereKey($payment->order_id)->lockForUpdate()->firstOrFail();

            // 以顧客實際付款的時間比對付款期限：期限前付款、但通知在期限後才（重新）送達，仍算準時
            if ($order->acceptsPayment($notification->paidAt)) {
                $order->transitionTo(OrderStatus::Paid);

                return;
            }

            if ($order->status === OrderStatus::PendingPayment) {
                ($this->expireOrder)($order);
            }

            // 其他狀態（例如另一次付款已讓訂單變成已付款）只記錄付款
            if ($order->status->canTransitionTo(OrderStatus::PaymentException)) {
                $order->transitionTo(OrderStatus::PaymentException);
            }
        });
    }
}
