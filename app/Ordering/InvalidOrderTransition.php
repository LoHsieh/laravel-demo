<?php

namespace App\Ordering;

use App\Models\Order;
use DomainException;

/**
 * 訂單狀態轉換不在 OrderStatus 定義的合法轉換中，或訂單的狀態已被別的請求改掉。
 */
final class InvalidOrderTransition extends DomainException
{
    public static function for(Order $order, OrderStatus $from, OrderStatus $to): self
    {
        return new self("訂單 {$order->number} 無法由{$from->label()}改為{$to->label()}。");
    }
}
