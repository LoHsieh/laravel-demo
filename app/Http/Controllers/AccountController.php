<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecipientRules;
use App\Ordering\ExpireOverdueOrders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 會員中心：顧客自己的訂單列表與預設收件資訊。
 */
class AccountController extends Controller
{
    public function show(Request $request, ExpireOverdueOrders $expireOverdueOrders): View
    {
        $customer = $request->user('customer');

        // 排程可能還沒處理（網站休眠），列表上不顯示已逾期的待付款訂單
        $expireOverdueOrders($customer);

        return view('account.show', [
            'customer' => $customer,
            'orders' => $customer->orders()->latest()->latest('id')->get(),
        ]);
    }

    public function updateRecipient(Request $request): RedirectResponse
    {
        $validated = $request->validate(RecipientRules::rules());

        $request->user('customer')->update($validated);

        return redirect()->route('account.show')->with('status', '已儲存預設收件資訊。');
    }
}
