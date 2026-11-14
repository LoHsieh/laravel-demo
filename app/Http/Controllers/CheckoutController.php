<?php

namespace App\Http\Controllers;

use App\Cart\Cart;
use App\Http\Requests\RecipientRules;
use App\Models\Order;
use App\Ordering\PlaceOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        $validated = $request->validate([
            ...RecipientRules::rules(),
            // 顧客在結帳頁看到的總計，金額在這之後變動時請顧客重新確認
            'expected_total' => ['required', 'integer'],
        ]);

        $order = $placeOrder(
            $request->user('customer'),
            $cart,
            Arr::only($validated, array_keys(RecipientRules::rules())),
            (int) $validated['expected_total'],
        );

        // 訂單成立頁會立即自動導向綠界付款頁
        return redirect()->route('checkout.placed', $order)->with('start_payment', true);
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
