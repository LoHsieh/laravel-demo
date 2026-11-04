<?php

namespace App\Http\Controllers;

use App\Catalog\Grind;
use App\Catalog\ProductFacets;
use App\Catalog\ProductFilters;
use App\Catalog\ProductSort;
use App\Catalog\Roast;
use App\Http\Resources\ProductCardResource;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    private const PER_PAGE = 12;

    /**
     * 詳情頁「你可能也喜歡」最多列出的咖啡豆數量。
     */
    private const MAX_RELATED = 8;

    /**
     * 「你可能也喜歡」收合時顯示的數量，超過時出現「查看更多」。
     */
    private const RELATED_VISIBLE = 4;

    /**
     * 列表頁。Vue 元件以同一個網址取得 JSON 結果，篩選、排序、換頁時不整頁重新載入。
     */
    public function index(Request $request): Response|JsonResponse
    {
        $filters = ProductFilters::fromRequest($request);
        $results = $this->results($filters, $request);

        // HTML 與 JSON 共用網址，快取須依 Accept 區分。
        if ($request->wantsJson()) {
            return response()->json($results)->setVary('Accept');
        }

        return response()->view('products.index', [
            'browser' => [
                'filters' => $filters->toQuery(),
                'results' => $results,
                'facets' => ProductFacets::forPublishedProducts(),
                'sortOptions' => array_map(
                    fn (ProductSort $sort) => ['value' => $sort->value, 'label' => $sort->label()],
                    ProductSort::cases(),
                ),
            ],
        ])->setVary('Accept');
    }

    /**
     * 咖啡豆詳情頁。規格與研磨方式選擇器是 Vue 元件，初始資料由這裡交給它。
     */
    public function show(Product $product): Response
    {
        abort_unless($product->is_published, 404);

        $product->load(['variants', 'flavorNotes']);

        return response()->view('products.show', [
            'product' => $product,
            'roast' => Roast::tryFrom($product->roast),
            'related' => Product::published()
                ->relatedTo($product)
                ->withCardSummary()
                ->limit(self::MAX_RELATED)
                ->get(),
            'relatedVisible' => self::RELATED_VISIBLE,
            'purchase' => [
                'variants' => $product->variants->map(fn (Variant $variant) => [
                    'id' => $variant->id,
                    'label' => "{$variant->weight_grams}g",
                    'price' => $variant->price,
                    'stock' => $variant->stock,
                    'sold_out' => $variant->stock === 0,
                ])->all(),
                'grinds' => array_map(
                    fn (Grind $grind) => ['value' => $grind->value, 'label' => $grind->label(), 'hint' => $grind->hint()],
                    Grind::cases(),
                ),
            ],
        ]);
    }

    /**
     * @return array{products: array<int, mixed>, pagination: array{current_page: int, last_page: int, total: int}}
     */
    private function results(ProductFilters $filters, Request $request): array
    {
        $page = Product::published()
            ->matching($filters)
            ->sortedBy($filters->sort)
            ->withCardSummary()
            ->paginate(self::PER_PAGE);

        return [
            'products' => ProductCardResource::collection($page->items())->resolve($request),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ];
    }
}
