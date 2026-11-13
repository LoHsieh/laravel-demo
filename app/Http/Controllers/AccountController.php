<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecipientRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 會員中心。目前只有預設收件資訊，訂單列表在之後的票加入。
 */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.show', ['customer' => $request->user('customer')]);
    }

    public function updateRecipient(Request $request): RedirectResponse
    {
        $validated = $request->validate(RecipientRules::rules());

        $request->user('customer')->update($validated);

        return redirect()->route('account.show')->with('status', '已儲存預設收件資訊。');
    }
}
