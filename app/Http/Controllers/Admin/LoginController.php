<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * 後台的登入頁，與顧客的登入頁分開，只接受店家帳號（admin guard）。
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * 只登出店家，不清空整個 session：同一個瀏覽器同時登入前台的顧客與購物車不受影響。
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
