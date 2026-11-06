<?php

namespace App\Http\Resources;

use App\Cart\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 購物車頁的一個購物車項目。
 *
 * @property CartItem $resource
 */
class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = $this->resource;
        $product = $item->variant->product;

        return [
            'key' => $item->key,
            'name' => $product->name,
            'origin_latin' => $product->origin_latin,
            'bag_color' => $product->bag_color,
            'url' => route('products.show', $product),
            'variant_label' => $item->variant->label,
            'grind_label' => $item->grind->label(),
            'price' => $item->variant->price,
            'quantity' => $item->quantity,
            'stock' => $item->variant->stock,
            'line_total' => $item->lineTotal(),
        ];
    }
}
