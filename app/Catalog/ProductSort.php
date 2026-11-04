<?php

namespace App\Catalog;

/**
 * 咖啡豆列表的排序方式。價格以咖啡豆最便宜的規格為準。
 */
enum ProductSort: string
{
    case Newest = 'newest';
    case PriceAscending = 'price_asc';
    case PriceDescending = 'price_desc';

    public function label(): string
    {
        return match ($this) {
            self::Newest => '最新上架',
            self::PriceAscending => '價格：低到高',
            self::PriceDescending => '價格：高到低',
        };
    }
}
