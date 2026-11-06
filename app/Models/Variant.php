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

#[Fillable(['weight_grams', 'price', 'stock'])]
class Variant extends Model
{
    /** @use HasFactory<VariantFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 可以加入購物車的規格：所屬咖啡豆仍上架。
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
