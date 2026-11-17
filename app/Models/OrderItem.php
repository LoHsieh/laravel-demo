<?php

namespace App\Models;

use App\Catalog\Grind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 訂單項目：一筆「規格 × 研磨方式 × 數量」。
 *
 * 品名、重量與單價都是下單當下的快照（ADR 0007），之後店家改名或調價不影響既有訂單。
 * 顯示訂單內容一律讀快照；規格只用來歸還庫存保留與連回規格。
 */
#[Fillable(['variant_id', 'product_name', 'weight_grams', 'grind', 'unit_price', 'quantity'])]
class OrderItem extends Model
{
    /**
     * 規格目前的品名與重量，下單時寫入訂單項目。
     *
     * @return array{variant_id: int, product_name: string, weight_grams: int}
     */
    public static function snapshotOf(Variant $variant): array
    {
        return [
            'variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'weight_grams' => $variant->weight_grams,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'variant_id' => 'integer',
            'weight_grams' => 'integer',
            'grind' => Grind::class,
            'unit_price' => 'integer',
            'quantity' => 'integer',
        ];
    }

    /**
     * 包含已刪除的規格：歸還庫存保留時，規格可能已被店家刪除（ADR 0006）。
     *
     * @return BelongsTo<Variant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class)->withTrashed();
    }

    /**
     * 顯示用的規格名稱，例如 250g，取自下單當下的重量。
     *
     * @return Attribute<string, never>
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn () => "{$this->weight_grams}g");
    }

    public function lineTotal(): int
    {
        return $this->unit_price * $this->quantity;
    }
}
