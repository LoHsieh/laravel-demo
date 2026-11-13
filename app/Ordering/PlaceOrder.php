<?php

namespace App\Ordering;

use App\Cart\Cart;
use App\Cart\CartItem;
use App\Cart\ShippingFee;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Variant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 結帳：把顧客的購物車變成一筆待付款訂單，並保留庫存。
 *
 * 在同一個資料庫交易中以 SELECT ... FOR UPDATE 鎖定並讀取各規格的庫存，全部足夠才扣除並建立訂單；
 * 任一規格不足就整筆失敗，不扣任何庫存。兩人同時買最後一包時，後拿到鎖的人會讀到已扣除的庫存而失敗。
 */
final class PlaceOrder
{
    public const PAYMENT_WINDOW_MINUTES = 30;

    /**
     * @param  array{recipient_name: string, recipient_phone: string, recipient_address: string}  $recipient
     * @param  int  $expectedTotal  顧客在結帳頁看到的總計；與實際金額不同時不成立訂單，請顧客重新確認
     *
     * @throws ValidationException 購物車是空的、有規格庫存不足或已下架、或金額已變動（錯誤放在 checkout）
     */
    public function __invoke(Customer $customer, Cart $cart, array $recipient, int $expectedTotal): Order
    {
        return DB::transaction(function () use ($customer, $cart, $recipient, $expectedTotal) {
            // 先鎖定顧客，同一位顧客重複送出時，後到的請求等前一筆完成後才讀購物車，讀到的已是清空後的購物車。
            // 這必須是交易中的第一個查詢：REPEATABLE READ 的快照在第一次一般讀取時建立。
            Customer::whereKey($customer->getKey())->lockForUpdate()->first();

            $items = $cart->items();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['checkout' => '購物車是空的，無法結帳。']);
            }

            $quantities = $items
                ->groupBy(fn (CartItem $item) => $item->variant->id)
                ->map(fn (Collection $group) => $group->sum('quantity'));

            $variants = $this->lockVariants($quantities->keys());
            $this->ensureAvailable($quantities, $variants, $items);

            $lines = $items->map(fn (CartItem $item) => [
                'variant_id' => $item->variant->id,
                'grind' => $item->grind,
                // 以鎖定後讀到的價格為準，與扣庫存讀的是同一份資料
                'unit_price' => $variants[$item->variant->id]->price,
                'quantity' => $item->quantity,
            ]);

            $subtotal = $lines->sum(fn (array $line) => $line['unit_price'] * $line['quantity']);
            $shippingFee = ShippingFee::for($subtotal);

            if ($subtotal + $shippingFee !== $expectedTotal) {
                throw ValidationException::withMessages(['checkout' => '訂單金額已變動，請確認新的總計後再送出。']);
            }

            foreach ($quantities as $variantId => $quantity) {
                $variants[$variantId]->decrement('stock', $quantity);
            }

            $order = $customer->orders()->create([
                ...$recipient,
                'number' => Order::newNumber(),
                'status' => OrderStatus::PendingPayment,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total' => $subtotal + $shippingFee,
                'payment_due_at' => now()->addMinutes(self::PAYMENT_WINDOW_MINUTES),
            ]);
            $order->items()->createMany($lines->all());

            $cart->clear();

            return $order;
        });
    }

    /**
     * 依 id 順序鎖定，多筆結帳同時鎖定多個規格時不會互相等待成死結。
     * 只取仍可購買的規格，讀購物車之後才下架或刪除的規格不會出現在結果中。
     *
     * @param  Collection<int, int>  $variantIds
     * @return Collection<int, Variant>
     */
    private function lockVariants(Collection $variantIds): Collection
    {
        return Variant::purchasable()
            ->with('product')
            ->whereKey($variantIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * 研磨方式不影響庫存（ADR 0001），以同一規格各研磨方式的數量合計比對庫存。
     *
     * @param  Collection<int, int>  $quantities  各規格的數量合計，以規格 id 為索引
     * @param  Collection<int, Variant>  $variants  鎖定後讀到的規格
     * @param  Collection<int, CartItem>  $items
     *
     * @throws ValidationException
     */
    private function ensureAvailable(Collection $quantities, Collection $variants, Collection $items): void
    {
        $problems = $quantities->map(function (int $quantity, int $variantId) use ($variants, $items) {
            $variant = $variants->get($variantId);

            if ($variant === null) {
                $item = $items->first(fn (CartItem $item) => $item->variant->id === $variantId);

                return "{$item->variant->product->name} {$item->variant->label}：已下架。";
            }

            if ($variant->stock >= $quantity) {
                return null;
            }

            $stock = $variant->stock === 0 ? '已售完' : "庫存僅剩 {$variant->stock} 包";

            return "{$variant->product->name} {$variant->label}：{$stock}，購物車中共有 {$quantity} 包。";
        })->filter();

        if ($problems->isNotEmpty()) {
            throw ValidationException::withMessages([
                'checkout' => ['庫存不足，訂單未成立。', ...$problems->values()],
            ]);
        }
    }
}
