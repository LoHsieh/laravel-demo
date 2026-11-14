<?php

namespace App\Ordering;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Payment\CheckoutForm;
use App\Payment\PaymentGateway;
use App\Payment\PaymentRequest;
use Illuminate\Validation\ValidationException;

/**
 * 開始一次付款：建立付款嘗試，取得導向金流服務付款頁的表單。付款失敗後再次呼叫即是重新付款。
 *
 * 金流服務無法限制付款頁的期限（ADR 0005），所以只在訂單待付款且未超過付款期限時才開始付款。
 */
final class StartPayment
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /**
     * @throws ValidationException 訂單不是待付款或已超過付款期限（錯誤放在 payment）
     */
    public function __invoke(Order $order): CheckoutForm
    {
        if (! $order->acceptsPayment()) {
            throw ValidationException::withMessages(['payment' => $order->status === OrderStatus::PendingPayment
                ? '已超過付款期限，無法付款。'
                : "訂單{$order->status->label()}，無法付款。"]);
        }

        $payment = $order->payments()->create([
            'merchant_trade_no' => Payment::newMerchantTradeNo($order),
            'status' => PaymentStatus::Pending,
            'amount' => $order->total,
        ]);

        return $this->gateway->checkoutForm(new PaymentRequest(
            merchantTradeNo: $payment->merchant_trade_no,
            amount: $payment->amount,
            itemNames: $order->items()->with('variant.product')->get()
                ->map(fn (OrderItem $item) => "{$item->variant->product->name} {$item->variant->label} x {$item->quantity}")
                ->all(),
            dueAt: $order->payment_due_at->toImmutable(),
        ));
    }
}
