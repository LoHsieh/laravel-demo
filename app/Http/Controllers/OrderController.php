<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Ordering\CancelOrder;
use App\Ordering\InvalidOrderTransition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 會員中心的訂單：顧客只能看到、取消自己的訂單，其他人的訂單一律 404，不透露訂單是否存在。
 */
class OrderController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        $this->ensureOwnedBy($request, $order);

        return view('account.orders.show', ['order' => $order->load('items.variant.product')]);
    }

    public function cancel(Request $request, Order $order, CancelOrder $cancelOrder): RedirectResponse
    {
        $this->ensureOwnedBy($request, $order);

        try {
            $cancelOrder($order);
        } catch (InvalidOrderTransition) {
            return redirect()->route('account.orders.show', $order)
                ->withErrors(['order' => '只有待付款的訂單可以取消。']);
        }

        return redirect()->route('account.orders.show', $order)->with('order_status', '訂單已取消，庫存保留已釋出。');
    }

    private function ensureOwnedBy(Request $request, Order $order): void
    {
        abort_unless($order->customer()->is($request->user('customer')), 404);
    }
}
