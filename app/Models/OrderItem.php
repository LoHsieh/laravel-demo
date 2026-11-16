<?php

namespace App\Models;

use App\Catalog\Grind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 訂單項目：一筆「規格 × 研磨方式 × 數量」，unit_price 是下單當下的單價。
 */
#[Fillable(['variant_id', 'grind', 'unit_price', 'quantity'])]
class OrderItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'variant_id' => 'integer',
            'grind' => Grind::class,
            'unit_price' => 'integer',
            'quantity' => 'integer',
        ];
    }

    /**
     * 包含已刪除的規格：店家刪除規格後，既有訂單仍顯示它（ADR 0006）。
     *
     * @return BelongsTo<Variant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class)->withTrashed();
    }

    public function lineTotal(): int
    {
        return $this->unit_price * $this->quantity;
    }
}
