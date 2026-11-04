<?php

namespace App\Models;

use App\Catalog\ProductFilters;
use App\Catalog\ProductSort;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'origin', 'process', 'roast', 'flavor_description', 'image', 'is_published', 'is_featured'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * 卡片眉標用的產地英文名稱。
     */
    private const ORIGIN_LATIN = [
        '衣索比亞' => 'ETHIOPIA',
        '肯亞' => 'KENYA',
        '哥倫比亞' => 'COLOMBIA',
        '瓜地馬拉' => 'GUATEMALA',
        '巴拿馬' => 'PANAMA',
        '哥斯大黎加' => 'COSTA RICA',
        '巴西' => 'BRAZIL',
        '印尼' => 'INDONESIA',
        '盧安達' => 'RWANDA',
        '薩爾瓦多' => 'EL SALVADOR',
    ];

    /**
     * 牛皮紙袋插圖的顏色，依咖啡豆輪替（docs/design/README.md）。
     */
    private const BAG_COLORS = ['#C6B193', '#BBA27F', '#A88F6E', '#CBB89B', '#8E765B'];

    /**
     * @return HasMany<Variant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class)->orderBy('weight_grams');
    }

    /**
     * @return BelongsToMany<FlavorNote, $this>
     */
    public function flavorNotes(): BelongsToMany
    {
        return $this->belongsToMany(FlavorNote::class);
    }

    /**
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * 同一組條件之間為「或」，不同組之間為「且」。
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function matching(Builder $query, ProductFilters $filters): void
    {
        $query
            ->when($filters->origins, fn (Builder $query, array $origins) => $query->whereIn('origin', $origins))
            ->when($filters->processes, fn (Builder $query, array $processes) => $query->whereIn('process', $processes))
            ->when($filters->roasts, fn (Builder $query, array $roasts) => $query->whereIn('roast', $roasts))
            ->when($filters->flavorNotes, fn (Builder $query, array $names) => $query->whereHas(
                'flavorNotes',
                fn (Builder $query) => $query->whereIn('name', $names),
            ))
            ->when($filters->keyword, function (Builder $query, string $keyword) {
                $pattern = '%'.addcslashes($keyword, '\\%_').'%';

                $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', $pattern)
                    ->orWhere('flavor_description', 'like', $pattern));
            });
    }

    /**
     * 附上列表卡片需要的最低售價（variants_min_price）與是否仍有庫存（has_stock）。
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function withCardSummary(Builder $query): void
    {
        $query
            ->withMin('variants', 'price')
            ->withExists(['variants as has_stock' => fn (Builder $query) => $query->where('stock', '>', 0)]);
    }

    /**
     * 價格排序以最便宜的規格為準，尚無規格（沒有價格）的排在最後，價格相同時較新的排前面。
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function sortedBy(Builder $query, ProductSort $sort): void
    {
        $variants = fn () => Variant::query()->whereColumn('variants.product_id', 'products.id');

        if ($sort !== ProductSort::Newest) {
            $query->orderBy($variants()->selectRaw('count(*) = 0'));
        }

        match ($sort) {
            ProductSort::PriceAscending => $query->orderBy($variants()->selectRaw('min(price)')),
            ProductSort::PriceDescending => $query->orderByDesc($variants()->selectRaw('min(price)')),
            ProductSort::Newest => null,
        };

        $query->latest()->orderByDesc('id');
    }

    /**
     * 不在對照表中的產地沿用中文名稱。
     *
     * @return Attribute<string, never>
     */
    protected function originLatin(): Attribute
    {
        return Attribute::get(fn () => self::ORIGIN_LATIN[$this->origin] ?? $this->origin);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function bagColor(): Attribute
    {
        return Attribute::get(fn () => self::BAG_COLORS[$this->id % count(self::BAG_COLORS)]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }
}
