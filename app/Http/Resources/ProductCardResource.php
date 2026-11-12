<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 列表與首頁卡片上的一支咖啡豆。查詢時須套用 Product::withCardSummary()。
 *
 * @mixin Product
 */
class ProductCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'origin' => $this->origin,
            'origin_latin' => $this->origin_latin,
            'process' => $this->process,
            'roast' => $this->roast->value,
            'flavor_description' => $this->flavor_description,
            'min_price' => $this->variants_min_price === null ? null : (int) $this->variants_min_price,
            'sold_out' => ! $this->has_stock,
            'bag_color' => $this->bag_color,
            'url' => route('products.show', $this->resource),
        ];
    }
}
