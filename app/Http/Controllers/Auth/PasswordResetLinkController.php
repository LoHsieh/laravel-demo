<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']]);

        // 不論 Email 是否註冊、是否剛寄過，都回應同一句話，避免被用來查詢誰是會員。
        Password::broker('customers')->sendResetLink($request->only('email'));

        return back()->with('status', __('passwords.sent'));
    }
}
