<?php

namespace App\Ordering;

use App\Models\Order;

/**
 * 把出貨已滿 7 天的已出貨訂單改為已完成。由排程執行。
 */
final class CompleteShippedOrders
{
    public function __invoke(): void
    {
        Order::dueForCompletion()
            // 依 id 分批：處理過的訂單會離開查詢條件，以 offset 分批會跳過其中一批
            ->lazyById()
            ->each(function (Order $order) {
                try {
                    $order->transitionTo(OrderStatus::Completed);
                } catch (InvalidOrderTransition) {
                    // 店家剛好同時手動改為已完成，不需再處理
                }
            });
    }
}
