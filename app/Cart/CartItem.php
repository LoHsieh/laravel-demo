<?php

namespace App\Cart;

use App\Catalog\Grind;
use App\Models\Variant;

/**
 * 購物車項目：一筆「規格 × 研磨方式 × 數量」，價格與庫存以規格目前的資料為準。
 */
final readonly class CartItem
{
    public function __construct(
        public string $key,
        public Variant $variant,
        public Grind $grind,
        public int $quantity,
    ) {}

    public function lineTotal(): int
    {
        return $this->variant->price * $this->quantity;
    }
}
