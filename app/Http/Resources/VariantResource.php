<?php

namespace App\Http\Resources;

use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 詳情頁購買區的一個規格。
 *
 * @mixin Variant
 */
class VariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => "{$this->weight_grams}g",
            'price' => $this->price,
            'stock' => $this->stock,
            'sold_out' => $this->stock === 0,
        ];
    }
}
