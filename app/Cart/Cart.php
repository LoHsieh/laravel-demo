<?php

namespace App\Cart;

use App\Catalog\Grind;
use App\Models\Variant;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * 訪客的購物車，存在 session 中。同一規格 + 同一研磨方式是同一個購物車項目，
 * 不同研磨方式則分開。session 只存規格、研磨方式與數量，價格與庫存每次從規格讀取。
 */
final class Cart
{
    private const SESSION_KEY = 'cart.items';

    public function __construct(private readonly Session $session) {}

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
        ];

        $this->session->put(self::SESSION_KEY, $entries);
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
        $this->session->put(self::SESSION_KEY, $entries);

        return true;
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
        $this->session->put(self::SESSION_KEY, $entries);

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
        $variants = Variant::purchasable()->with('product')->findMany(array_column($entries, 'variant_id'))->keyBy('id');

        return collect($entries)
            ->filter(fn (array $entry) => $variants->has($entry['variant_id']))
            ->map(fn (array $entry, string $key) => new CartItem(
                $key,
                $variants[$entry['variant_id']],
                Grind::from($entry['grind']),
                $entry['quantity'],
            ))
            ->values();
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
     * @param  array<string, array{variant_id: int, grind: string, quantity: int}>  $entries
     */
    private function quantityOf(Variant $variant, array $entries): int
    {
        return collect($entries)->where('variant_id', $variant->id)->sum('quantity');
    }

    private static function keyFor(Variant $variant, Grind $grind): string
    {
        return "{$variant->id}-{$grind->value}";
    }

    /**
     * @return array<string, array{variant_id: int, grind: string, quantity: int}>
     */
    private function entries(): array
    {
        return $this->session->get(self::SESSION_KEY, []);
    }
}
