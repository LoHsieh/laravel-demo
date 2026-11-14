<?php

namespace App\Ordering;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;

/**
 * 取消待付款訂單並歸還庫存保留。顧客取消與付款逾期走這同一條路徑。
 *
 * 狀態轉換與歸還庫存在同一個交易中。Order::transitionTo() 以「狀態仍是待付款」為條件更新，
 * 同一筆訂單被取消兩次（重複送出、顧客取消與排程同時進行）時只有一次成功，庫存也就只歸還一次；
 * 失敗的那次丟出 InvalidOrderTransition，不動任何庫存。
 */
final class CancelOrder
{
    /**
     * @throws InvalidOrderTransition 訂單不是待付款（已付款、已取消等）
     */
    public function __invoke(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->transitionTo(OrderStatus::Cancelled);

            // 同一規格不同研磨方式是不同的訂單項目，合計後依 id 順序歸還，與結帳鎖定規格的順序一致，避免死結
            $order->items()->get()
                ->groupBy('variant_id')
                ->map(fn ($items) => $items->sum(fn (OrderItem $item) => $item->quantity))
                ->sortKeys()
                ->each(fn (int $quantity, int $variantId) => Variant::whereKey($variantId)->increment('stock', $quantity));
        });
    }
}
