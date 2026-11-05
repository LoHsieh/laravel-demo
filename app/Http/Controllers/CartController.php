<?php

namespace App\Http\Controllers;

use App\Cart\Cart;
use App\Cart\ShippingFee;
use App\Catalog\Grind;
use App\Http\Resources\CartItemResource;
use App\Models\Variant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    /**
     * 購物車頁，要求 JSON 時回傳購物車內容。加入、修改與移除也都回傳同樣格式的購物車，
     * 由 Vue 元件直接換上。
     */
    public function show(Request $request, Cart $cart): Response|JsonResponse
    {
        $summary = $this->summary($cart, $request);

        if ($request->wantsJson()) {
            return response()->json($summary)->setVary('Accept');
        }

        return response()->view('cart.show', [
            'cart' => $summary,
            'urls' => [
                'items' => route('cart.items.store'),
                'browse' => route('products.index'),
            ],
        ])->setVary('Accept');
    }

    public function store(Request $request, Cart $cart): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer'],
            'grind' => ['required', Rule::enum(Grind::class)],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variant = Variant::whereRelation('product', 'is_published', true)->findOrFail($validated['variant_id']);

        $cart->add($variant, Grind::from($validated['grind']), $validated['quantity']);

        return response()->json($this->summary($cart, $request));
    }

    public function update(Request $request, Cart $cart, string $key): JsonResponse
    {
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);

        abort_unless($cart->changeQuantity($key, $validated['quantity']), 404);

        return response()->json($this->summary($cart, $request));
    }

    public function destroy(Request $request, Cart $cart, string $key): JsonResponse
    {
        abort_unless($cart->remove($key), 404);

        return response()->json($this->summary($cart, $request));
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Cart $cart, Request $request): array
    {
        $summary = $cart->summary();

        return [
            'items' => CartItemResource::collection($summary->items)->resolve($request),
            'count' => $cart->count(),
            'subtotal' => $summary->subtotal,
            'shipping_fee' => $summary->shippingFee,
            'total' => $summary->total(),
            'free_shipping_remaining' => $summary->freeShippingRemaining(),
            'free_shipping_threshold' => ShippingFee::FREE_THRESHOLD,
        ];
    }
}
