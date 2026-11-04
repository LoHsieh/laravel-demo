<?php

namespace App\Catalog;

use Illuminate\Http\Request;

/**
 * 訪客在咖啡豆列表選擇的篩選條件，來源是網址的查詢字串。
 */
final readonly class ProductFilters
{
    /**
     * @param  list<string>  $origins
     * @param  list<string>  $processes
     * @param  list<string>  $roasts
     * @param  list<string>  $flavorNotes  風味標籤名稱
     * @param  string  $keyword  比對咖啡豆名稱與風味描述，空字串代表不搜尋
     */
    public function __construct(
        public array $origins = [],
        public array $processes = [],
        public array $roasts = [],
        public array $flavorNotes = [],
        public string $keyword = '',
        public ProductSort $sort = ProductSort::Newest,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            origins: self::cleanStrings($request->query('origin')),
            processes: self::cleanStrings($request->query('process')),
            roasts: self::cleanStrings($request->query('roast')),
            flavorNotes: self::cleanStrings($request->query('flavor')),
            keyword: self::cleanStrings($request->query('q'))[0] ?? '',
            sort: ProductSort::tryFrom(self::cleanStrings($request->query('sort'))[0] ?? '') ?? ProductSort::Newest,
        );
    }

    /**
     * 與網址查詢字串相同的格式，交給前端作為初始狀態。
     *
     * @return array{origin: list<string>, process: list<string>, roast: list<string>, flavor: list<string>, q: string, sort: string}
     */
    public function toQuery(): array
    {
        return [
            'origin' => $this->origins,
            'process' => $this->processes,
            'roast' => $this->roasts,
            'flavor' => $this->flavorNotes,
            'q' => $this->keyword,
            'sort' => $this->sort->value,
        ];
    }

    /**
     * 分享的網址可能被手動修改，非預期的型別一律忽略而不是報錯。
     *
     * @return list<string>
     */
    private static function cleanStrings(mixed $value): array
    {
        return collect(is_array($value) ? $value : [$value])
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->map(fn (string $item) => trim($item))
            ->unique()
            ->values()
            ->all();
    }
}
