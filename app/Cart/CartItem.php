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
        /** 顧客上次看到的價格，與目前價格不同時提示 */
        public ?int $seenPrice = null,
        /** 購物車中這個規格所有研磨方式的數量合計，超過庫存時提示 */
        public int $variantQuantityInCart = 0,
    ) {}

    /**
     * 規格的價格或庫存在加入購物車後變動時給顧客的提示。庫存不足優先，因為它會擋住結帳。
     */
    public function notice(): ?string
    {
        $stock = $this->variant->stock;

        if ($stock === 0) {
            return '此規格已售完，請移除。';
        }

        if ($this->variantQuantityInCart > $stock) {
            return "此規格目前僅剩 {$stock} 包，購物車中共有 {$this->variantQuantityInCart} 包，請調降數量。";
        }

        if ($this->seenPrice !== null && $this->seenPrice !== $this->variant->price) {
            return '價格已由 NT$'.number_format($this->seenPrice).' 調整為 NT$'.number_format($this->variant->price).'。';
        }

        return null;
    }

    public function lineTotal(): int
    {
        return $this->variant->price * $this->quantity;
    }
}
