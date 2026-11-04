<?php

namespace App\Http\Controllers;

use App\Catalog\ProductFacets;
use App\Catalog\ProductFilters;
use App\Catalog\ProductSort;
use App\Http\Resources\ProductCardResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    private const PER_PAGE = 12;

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
