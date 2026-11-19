<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * 店家進入後台時看到的銷售概況：今日與本月的營收、訂單數，以及本月的熱銷咖啡豆。
 * 只計入已付款、已出貨、已完成的訂單，期間以下單時間在店家時區的日期為準。
 */
class DashboardController extends Controller
{
    /** 熱銷咖啡豆最多列出幾支。 */
    private const TOP_PRODUCTS = 5;

    public function __invoke(): View
    {
        $now = now(config('app.shop_timezone'));
        $today = $this->ordersBetween($now->copy()->startOfDay(), $now->copy()->endOfDay());
        $thisMonth = $this->ordersBetween($now->copy()->startOfMonth(), $now->copy()->endOfMonth());

        return view('admin.dashboard', [
            'periods' => [
                '今日' => $this->sales($today),
                '本月' => $this->sales($thisMonth),
            ],
            'topProducts' => $this->topProducts($thisMonth),
        ]);
    }

    /**
     * 店家時區的一段期間內下單、計入營收的訂單。資料庫裡的時間是 UTC，查詢前先把起訖換成 UTC。
     *
     * @return Builder<Order>
     */
    private function ordersBetween(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return Order::countedAsRevenue()->whereBetween('created_at', [$from->copy()->utc(), $to->copy()->utc()]);
    }

    /**
     * @param  Builder<Order>  $orders
     * @return array{revenue: int, orderCount: int}
     */
    private function sales(Builder $orders): array
    {
        return ['revenue' => (int) $orders->clone()->sum('total'), 'orderCount' => $orders->clone()->count()];
    }

    /**
     * 依售出包數排名的咖啡豆，各規格合併計算。透過規格歸到咖啡豆（含已刪除的規格，ADR 0006），
     * 顯示咖啡豆目前的名稱，店家改名後新舊訂單仍算同一支。
     *
     * @param  Builder<Order>  $orders
     * @return Collection<int, object{name: string, bags: int}>
     */
    private function topProducts(Builder $orders): Collection
    {
        return OrderItem::query()
            ->join('variants', 'variants.id', '=', 'order_items.variant_id')
            ->join('products', 'products.id', '=', 'variants.product_id')
            ->whereIn('order_items.order_id', $orders->clone()->select('orders.id'))
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.name as name, SUM(order_items.quantity) as bags')
            ->orderByDesc('bags')
            ->orderBy('products.id')
            ->limit(self::TOP_PRODUCTS)
            ->toBase()
            ->get()
            ->map(fn (object $row) => (object) ['name' => $row->name, 'bags' => (int) $row->bags]);
    }
}
