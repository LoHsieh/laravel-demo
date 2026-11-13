<?php

namespace App\Cart;

use App\Catalog\Grind;
use App\Models\Variant;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * 購物車：訪客的存在 session，顧客的存在資料庫（見 CartStore）。同一規格 + 同一研磨方式是同一個
 * 購物車項目，不同研磨方式則分開。只存規格、研磨方式、數量與顧客上次看到的價格（seen_price）；
 * 價格與庫存每次從規格讀取，seen_price 只用來提示價格變動。
 *
 * @phpstan-import-type Entry from CartStore
 */
final class Cart
{
    public function __construct(private readonly CartStore $store) {}

    /**
     * 研磨方式不影響庫存（ADR 0001），所以同一規格各研磨方式的數量合計不能超過庫存。
     *
     * @throws ValidationException
     */
    public function add(Variant $variant, Grind $grind, int $quantity): void
    {
        $key = self::keyFor($variant, $grind);
        $entries = $this->entries();
        $inCart = $this->quantityOf($variant, $entries);

        if ($inCart + $quantity > $variant->stock) {
            throw StockLimitExceeded::whenAdding($variant, $inCart);
        }

        $entries[$key] = [
            'variant_id' => $variant->id,
            'grind' => $grind->value,
            'quantity' => ($entries[$key]['quantity'] ?? 0) + $quantity,
            'seen_price' => $variant->price,
        ];

        $this->store->save($entries);
    }

    /**
     * 把購物車項目的數量改成指定值。購物車中沒有這個項目時回傳 false。
     * 庫存在加入後減少時，仍可把數量往下調，只是不能再調高。
     *
     * @throws ValidationException
     */
    public function changeQuantity(string $key, int $quantity): bool
    {
        $entries = $this->entries();
        $variant = isset($entries[$key]) ? Variant::purchasable()->find($entries[$key]['variant_id']) : null;

        if ($variant === null) {
            return false;
        }

        $inOtherItems = $this->quantityOf($variant, $entries) - $entries[$key]['quantity'];

        $lowering = $quantity < $entries[$key]['quantity'];

        if (! $lowering && $inOtherItems + $quantity > $variant->stock) {
            throw StockLimitExceeded::whenChanging($variant, $inOtherItems);
        }

        $entries[$key]['quantity'] = $quantity;
        $this->store->save($entries);

        return true;
    }

    /**
     * 併入另一個購物車（登入前的訪客購物車）的項目：相同規格與研磨方式的數量相加，其餘依序加在後面。
     * 同一規格各研磨方式的合計不超過庫存，超過的部分調整到上限；已下架或不存在的規格略過。
     * 原本就在這個購物車裡的數量不會被調降。訪客看到的價格跟著併入，登入後仍會提示價格變動。
     *
     * @param  array<string, Entry>  $incoming
     * @return list<string> 因庫存而調整的購物車項目，可直接顯示給顧客的說明
     */
    public function merge(array $incoming): array
    {
        $entries = $this->entries();
        $variants = $this->purchasableVariantsOf($incoming);
        $adjustments = [];

        foreach ($incoming as $key => $entry) {
            $variant = $variants->get($entry['variant_id']);

            if ($variant === null) {
                continue;
            }

            $existing = $entries[$key]['quantity'] ?? 0;
            $room = $variant->stock - ($this->quantityOf($variant, $entries) - $existing);
            $wanted = $existing + $entry['quantity'];
            $merged = max($existing, min($wanted, $room));

            if ($merged < $wanted) {
                $adjustments[] = self::describeAdjustment($variant, Grind::from($entry['grind']), $merged);
            }

            if ($merged === 0) {
                continue;
            }

            $entries[$key] = [
                'variant_id' => $variant->id,
                'grind' => $entry['grind'],
                'quantity' => $merged,
                'seen_price' => $entries[$key]['seen_price'] ?? $entry['seen_price'] ?? $variant->price,
            ];
        }

        $this->store->save($entries);

        return $adjustments;
    }

    private static function describeAdjustment(Variant $variant, Grind $grind, int $merged): string
    {
        $item = "{$variant->product->name} {$variant->label}・{$grind->label()}";
        $stock = $variant->stock === 0 ? '已售完' : "庫存僅剩 {$variant->stock} 包";

        return $merged === 0
            ? "{$item}：{$stock}，未併入購物車。"
            : "{$item}：{$stock}，合併後調整為 {$merged} 包。";
    }

    /**
     * 移除購物車項目。購物車中沒有這個項目時回傳 false。
     */
    public function remove(string $key): bool
    {
        $entries = $this->entries();

        if (! isset($entries[$key])) {
            return false;
        }

        unset($entries[$key]);
        $this->store->save($entries);

        return true;
    }

    /**
     * 購物車項目，依加入順序排列。規格已不存在或咖啡豆已下架的項目略過。
     *
     * @return Collection<int, CartItem>
     */
    public function items(): Collection
    {
        $entries = $this->entries();
        $variants = $this->purchasableVariantsOf($entries);

        return collect($entries)
            ->filter(fn (array $entry) => $variants->has($entry['variant_id']))
            ->map(fn (array $entry, string $key) => new CartItem(
                $key,
                $variants[$entry['variant_id']],
                Grind::from($entry['grind']),
                $entry['quantity'],
                $entry['seen_price'] ?? null,
                $this->quantityOf($variants[$entry['variant_id']], $entries),
            ))
            ->values();
    }

    /**
     * 顧客已在購物車頁看到價格變動的提示，之後以目前價格為準，不再提示。
     */
    public function acknowledgePrices(): void
    {
        $entries = $this->entries();
        $variants = $this->purchasableVariantsOf($entries);
        $changed = false;

        foreach ($entries as $key => $entry) {
            $price = $variants->get($entry['variant_id'])?->price;

            if ($price !== null && ($entry['seen_price'] ?? null) !== $price) {
                $entries[$key]['seen_price'] = $price;
                $changed = true;
            }
        }

        if ($changed) {
            $this->store->save($entries);
        }
    }

    public function summary(): CartSummary
    {
        return new CartSummary($this->items());
    }

    /**
     * 頁首顯示的商品數量：所有購物車項目的數量總和，與購物車頁列出的項目一致。
     */
    public function count(): int
    {
        return $this->items()->sum('quantity');
    }

    /**
     * @param  array<string, Entry>  $entries
     */
    private function quantityOf(Variant $variant, array $entries): int
    {
        return collect($entries)->where('variant_id', $variant->id)->sum('quantity');
    }

    /**
     * @param  array<string, Entry>  $entries
     * @return Collection<int, Variant>
     */
    private function purchasableVariantsOf(array $entries): Collection
    {
        return Variant::purchasable()->with('product')->findMany(array_column($entries, 'variant_id'))->keyBy('id');
    }

    private static function keyFor(Variant $variant, Grind $grind): string
    {
        return self::keyOf($variant->id, $grind->value);
    }

    /**
     * 購物車項目的 key，也是購物車頁修改與移除時網址中的識別。
     */
    public static function keyOf(int $variantId, string $grind): string
    {
        return "{$variantId}-{$grind}";
    }

    /**
     * @return array<string, Entry>
     */
    private function entries(): array
    {
        return $this->store->entries();
    }
}
