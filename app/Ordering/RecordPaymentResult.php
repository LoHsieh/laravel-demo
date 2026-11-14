<?php

namespace App\Ordering;

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
 * - 已取消或已逾期的訂單收到付款成功只記錄付款，訂單不變成已付款（付款異常見 #12）。
 */
final class RecordPaymentResult
{
    /**
     * @throws InvalidPaymentNotification 找不到這次付款嘗試，或金額與付款嘗試不符
     */
    public function __invoke(PaymentNotification $notification): void
    {
        DB::transaction(function () use ($notification) {
            // 鎖定付款嘗試，同一筆通知同時送達兩次時，後到的會等前一筆完成，再讀到已記錄的結果。
            $payment = Payment::where('merchant_trade_no', $notification->merchantTradeNo)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                throw new InvalidPaymentNotification("找不到交易編號 {$notification->merchantTradeNo} 的付款。");
            }

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

            $order = $payment->order;

            // 以顧客實際付款的時間比對付款期限：期限前付款、但通知在期限後才（重新）送達，仍算準時
            if ($notification->succeeded && $order->acceptsPayment($notification->paidAt)) {
                $order->transitionTo(OrderStatus::Paid);
            }
        });
    }
}
