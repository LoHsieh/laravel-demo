<?php

namespace App\Ordering;

/**
 * 訂單狀態，以及唯一一份合法轉換的定義。前台、後台、付款通知都透過 Order::transitionTo() 改狀態。
 *
 *   待付款 ──付款成功──▶ 已付款 ──店家出貨──▶ 已出貨 ──店家完成 或 出貨滿 7 天──▶ 已完成
 *     ├──付款逾期──▶ 已取消
 *     └──顧客取消──▶ 已取消 ──事後收到付款成功──▶ 付款異常
 */
enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case PaymentException = 'payment_exception';

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, match ($this) {
            self::PendingPayment => [self::Paid, self::Cancelled],
            self::Paid => [self::Shipped],
            self::Shipped => [self::Completed],
            self::Cancelled => [self::PaymentException],
            self::Completed, self::PaymentException => [],
        }, true);
    }

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => '待付款',
            self::Paid => '已付款',
            self::Shipped => '已出貨',
            self::Completed => '已完成',
            self::Cancelled => '已取消',
            self::PaymentException => '付款異常',
        };
    }
}
