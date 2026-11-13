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
     *
     * @throws ValidationException 購物車是空的，或有規格庫存不足（錯誤放在 checkout）
     */
    public function __invoke(Customer $customer, Cart $cart, array $recipient): Order
    {
        return DB::transaction(function () use ($customer, $cart, $recipient) {
            $items = $cart->items();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['checkout' => '購物車是空的，無法結帳。']);
            }

            $variants = $this->lockVariants($items);
            $this->ensureEnoughStock($items, $variants);

            $lines = $items->map(fn (CartItem $item) => [
                'variant_id' => $item->variant->id,
                'grind' => $item->grind,
                // 以鎖定後讀到的價格為準，與扣庫存讀的是同一份資料
                'unit_price' => $variants[$item->variant->id]->price,
                'quantity' => $item->quantity,
            ]);

            foreach ($this->quantitiesByVariant($items) as $variantId => $quantity) {
                $variants[$variantId]->decrement('stock', $quantity);
            }

            $subtotal = $lines->sum(fn (array $line) => $line['unit_price'] * $line['quantity']);
            $shippingFee = ShippingFee::for($subtotal);

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
     *
     * @param  Collection<int, CartItem>  $items
     * @return Collection<int, Variant>
     */
    private function lockVariants(Collection $items): Collection
    {
        return Variant::with('product')
            ->whereKey($this->quantitiesByVariant($items)->keys())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * 研磨方式不影響庫存（ADR 0001），同一規格各研磨方式的數量合計。
     *
     * @param  Collection<int, CartItem>  $items
     * @return Collection<int, int>
     */
    private function quantitiesByVariant(Collection $items): Collection
    {
        return $items
            ->groupBy(fn (CartItem $item) => $item->variant->id)
            ->map(fn (Collection $group) => $group->sum('quantity'));
    }

    /**
     * @param  Collection<int, CartItem>  $items
     * @param  Collection<int, Variant>  $variants
     *
     * @throws ValidationException
     */
    private function ensureEnoughStock(Collection $items, Collection $variants): void
    {
        $shortages = $this->quantitiesByVariant($items)
            ->filter(fn (int $quantity, int $variantId) => $variants[$variantId]->stock < $quantity)
            ->map(function (int $quantity, int $variantId) use ($variants) {
                $variant = $variants[$variantId];
                $stock = $variant->stock === 0 ? '已售完' : "庫存僅剩 {$variant->stock} 包";

                return "{$variant->product->name} {$variant->label}：{$stock}，購物車中共有 {$quantity} 包。";
            });

        if ($shortages->isNotEmpty()) {
            throw ValidationException::withMessages([
                'checkout' => ['庫存不足，訂單未成立。', ...$shortages->values()],
            ]);
        }
    }
}
