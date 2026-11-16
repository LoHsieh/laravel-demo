<?php

namespace App\Models;

use Database\Factories\VariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 規格：一支咖啡豆以特定重量販售的版本，各自有價格與庫存。
 *
 * 刪除是軟刪除（ADR 0006）：已刪除的規格被全域範圍排除，不出現在前台、不能加入購物車或結帳；
 * 訂單項目讀取規格與取消訂單歸還庫存時要用 withTrashed()。
 */
#[Fillable(['weight_grams', 'price', 'stock'])]
class Variant extends Model
{
    /** @use HasFactory<VariantFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 可以加入購物車的規格：所屬咖啡豆仍上架（已刪除的規格由軟刪除的全域範圍排除）。
     *
     * @param  Builder<Variant>  $query
     */
    #[Scope]
    protected function purchasable(Builder $query): void
    {
        $query->whereRelation('product', 'is_published', true);
    }

    /**
     * 顯示用的規格名稱，例如 250g。
     *
     * @return Attribute<string, never>
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn () => "{$this->weight_grams}g");
    }
}
