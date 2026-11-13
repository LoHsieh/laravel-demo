<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Ordering\StartPayment;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 顧客付款：導向金流服務的付款頁，以及付款完成後回到網站看到的付款結果。
 * 只有訂單的擁有者能操作，其他人一律 404，不透露訂單是否存在。
 */
class PaymentController extends Controller
{
    public function store(Request $request, Order $order, StartPayment $startPayment): View
    {
        abort_unless($order->customer()->is($request->user('customer')), 404);

        return view('payments.redirect', ['form' => $startPayment($order)]);
    }

    /**
     * 付款結果頁。顯示的是付款通知記錄下來的結果；通知還沒到時請顧客稍後重新整理。
     */
    public function show(Request $request, Order $order): View
    {
        abort_unless($order->customer()->is($request->user('customer')), 404);

        return view('payments.show', [
            'order' => $order,
            'payment' => $order->payments()->latest('id')->first(),
        ]);
    }
}
