<?php

namespace App\Cart;

use Illuminate\Support\Collection;

/**
 * 購物車項目與金額：商品小計、運費、總計，購物車頁與結帳頁顯示這份計算。運費一律來自 ShippingFee；
 * 訂單成立時以鎖定後讀到的規格價格重算一次（PlaceOrder），與結帳頁顯示的總計不同時不成立訂單。
 */
final readonly class CartSummary
{
    public int $subtotal;

    public int $shippingFee;

    /**
     * @param  Collection<int, CartItem>  $items
     */
    public function __construct(public Collection $items)
    {
        $this->subtotal = $items->sum(fn (CartItem $item) => $item->lineTotal());
        $this->shippingFee = ShippingFee::for($this->subtotal);
    }

    public function total(): int
    {
        return $this->subtotal + $this->shippingFee;
    }

    public function freeShippingRemaining(): int
    {
        return ShippingFee::remainingForFree($this->subtotal);
    }
}
