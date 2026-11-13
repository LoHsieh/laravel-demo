<?php

namespace App\Http\Controllers;

use App\Cart\Cart;
use App\Models\Order;
use App\Ordering\PlaceOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 結帳：只有顧客能進入；訪客會先被導向登入或註冊，完成後回到這裡（#8）。
 */
class CheckoutController extends Controller
{
    public function show(Request $request, Cart $cart): View|RedirectResponse
    {
        $summary = $cart->summary();

        if ($summary->items->isEmpty()) {
            return redirect()->route('cart.show');
        }

        return view('checkout.show', [
            'summary' => $summary,
            'customer' => $request->user('customer'),
        ]);
    }

    public function store(Request $request, Cart $cart, PlaceOrder $placeOrder): RedirectResponse
    {
        $recipient = $request->validate([
            'recipient_name' => ['required', 'string', 'max:50'],
            // 與會員中心的預設收件資訊相同規則
            'recipient_phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9()\s-]{8,20}$/'],
            'recipient_address' => ['required', 'string', 'max:255'],
        ]);

        $order = $placeOrder($request->user('customer'), $cart, $recipient);

        return redirect()->route('checkout.placed', $order);
    }

    /**
     * 訂單成立頁。只有訂單的擁有者看得到，其他人一律 404，不透露訂單是否存在。
     */
    public function placed(Request $request, Order $order): View
    {
        abort_unless($order->customer()->is($request->user('customer')), 404);

        return view('checkout.placed', ['order' => $order]);
    }
}
