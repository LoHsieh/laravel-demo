<?php

namespace App\Cart;

/**
 * 購物車項目存放的地方：訪客存在 session，顧客存在資料庫。Cart 只透過這裡讀寫，
 * 不在意購物車屬於誰。
 *
 * @phpstan-type Entry array{variant_id: int, grind: string, quantity: int, seen_price?: int|null}
 */
interface CartStore
{
    /**
     * 依加入順序排列、以購物車項目 key 為索引的項目。
     *
     * @return array<string, Entry>
     */
    public function entries(): array;

    /**
     * 以這份項目取代整個購物車，保留既有項目的順序。
     *
     * @param  array<string, Entry>  $entries
     */
    public function save(array $entries): void;
}
