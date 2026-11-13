<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * 結帳流程的入口。只有顧客能進入；訪客會先被導向登入或註冊，完成後回到這裡（#8）。
 * 收件資訊、庫存保留與建立訂單在 #9 加入。
 */
class CheckoutController extends Controller
{
    public function show(): View
    {
        return view('checkout.show');
    }
}
