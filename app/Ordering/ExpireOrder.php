<?php

namespace App\Ordering;

use App\Cart\Cart;
use App\Cart\CustomerCartStore;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

/**
 * 付款逾期：取消已超過付款期限的待付款訂單（與顧客取消同一條路徑，歸還庫存保留），
 * 並把訂單項目放回顧客的購物車。
 *
 * 放回購物車與取消在同一個交易中。同一筆訂單同時被處理兩次（排程與顧客查看訂單）時，
 * 後到的那次在 Order::transitionTo() 失敗，整個交易回滾，庫存與購物車都只變動一次。
 *
 * 先鎖定顧客，與結帳（PlaceOrder）互斥：結帳讀購物車到清空之間不會有項目被放回而一併清掉。
 * 鎖定順序一律是 顧客 → 訂單 → 規格，與結帳、付款通知一致，避免死結。顧客在購物車頁加入或修改
 * 項目不鎖定顧客，與放回同時發生時仍可能蓋掉其中一邊；機率極低且不影響訂單與庫存，所以不處理。
 */
final class ExpireOrder
{
    public function __construct(private readonly CancelOrder $cancelOrder) {}

    /**
     * @throws InvalidOrderTransition 訂單不是待付款，或已被別的請求處理
     */
    public function __invoke(Order $order): void
    {
        DB::transaction(function () use ($order) {
            Customer::whereKey($order->customer_id)->lockForUpdate()->first();

            ($this->cancelOrder)($order);

            // 以訂單項目的單價作為顧客看到的價格，價格已變動時購物車會提示
            $notes = (new Cart(new CustomerCartStore($order->customer)))->merge(
                $order->items()->get()->mapWithKeys(fn (OrderItem $item) => [
                    Cart::keyOf($item->variant_id, $item->grind->value) => [
                        'variant_id' => $item->variant_id,
                        'grind' => $item->grind->value,
                        'quantity' => $item->quantity,
                        'seen_price' => $item->unit_price,
                    ],
                ])->all(),
            );

            // 排程沒有 session 可以提示顧客，調整與略過的說明存在訂單上，於訂單詳情顯示
            $order->forceFill(['cart_restore_notes' => $notes])->save();
        });
    }
}
