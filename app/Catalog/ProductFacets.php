<?php

namespace App\Catalog;

use App\Models\FlavorNote;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

/**
 * 列表篩選欄的四組選項，各選項附上有多少支上架中的咖啡豆符合。
 * 數量不隨目前的篩選變動，與設計稿一致。
 */
final class ProductFacets
{
    /**
     * @return list<array{key: string, label: string, options: list<array{value: string, count: int}>}>
     */
    public static function forPublishedProducts(): array
    {
        return [
            ['key' => 'flavor', 'label' => '風味', 'options' => self::flavorNotes()],
            ['key' => 'origin', 'label' => '產地', 'options' => self::byColumn('origin')],
            ['key' => 'process', 'label' => '處理法', 'options' => self::byColumn('process')],
            ['key' => 'roast', 'label' => '烘焙度', 'options' => self::roasts()],
        ];
    }

    /**
     * @return list<array{value: string, count: int}>
     */
    private static function flavorNotes(): array
    {
        return FlavorNote::query()
            ->withCount(['products' => fn (Builder $query) => $query->where('is_published', true)])
            ->oldest('id')
            ->get()
            ->where('products_count', '>', 0)
            ->map(fn (FlavorNote $note) => ['value' => $note->name, 'count' => $note->products_count])
            ->values()
            ->all();
    }

    /**
     * 依數量由多到少。
     *
     * @return list<array{value: string, count: int}>
     */
    private static function byColumn(string $column): array
    {
        return Product::published()
            ->toBase()
            ->selectRaw("{$column} as value, count(*) as count")
            ->groupBy($column)
            ->orderByDesc('count')
            ->orderBy($column)
            ->get()
            ->map(fn (object $row) => ['value' => $row->value, 'count' => (int) $row->count])
            ->all();
    }

    /**
     * 依烘焙度由淺到深。
     *
     * @return list<array{value: string, count: int}>
     */
    private static function roasts(): array
    {
        return collect(self::byColumn('roast'))
            ->sortBy(fn (array $option) => Roast::from($option['value'])->level())
            ->values()
            ->all();
    }
}
