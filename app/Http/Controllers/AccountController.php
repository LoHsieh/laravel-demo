<?php

namespace App\Http\Controllers;

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
        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'max:50'],
            // 手機或市話，允許 +、括號、空白與連字號
            'recipient_phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9()\s-]{8,20}$/'],
            'recipient_address' => ['required', 'string', 'max:255'],
        ]);

        $request->user('customer')->update($validated);

        return redirect()->route('account.show')->with('status', '已儲存預設收件資訊。');
    }
}
