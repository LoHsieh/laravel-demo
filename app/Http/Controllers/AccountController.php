<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecipientRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 會員中心：顧客自己的訂單列表與預設收件資訊。
 */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $customer = $request->user('customer');

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
