<?php

namespace App\Http\Controllers;

use App\Catalog\ProductSort;
use App\Catalog\Roast;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    private const LATEST_COUNT = 8;

    public function __invoke(): View
    {
        // 沒有設定精選時，以最新上架的咖啡豆作為主視覺。
        $featured = Product::published()->where('is_featured', true)->sortedBy(ProductSort::Newest)->first()
            ?? Product::published()->sortedBy(ProductSort::Newest)->first();

        return view('home', [
            'featured' => $featured,
            'featuredRoast' => $featured ? Roast::tryFrom($featured->roast)?->latin() : null,
            'latestProducts' => Product::published()
                ->sortedBy(ProductSort::Newest)
                ->withCardSummary()
                ->limit(self::LATEST_COUNT)
                ->get(),
        ]);
    }
}
