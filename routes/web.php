<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\EcpayController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Middleware\LowercaseEmail;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', HomeController::class)->name('home');
Route::get('/beans', [ProductController::class, 'index'])->name('products.index');
Route::get('/beans/{product}', [ProductController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{key}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{key}', [CartController::class, 'destroy'])->name('cart.items.destroy');

// 顧客帳號。店家後台另有自己的 guard 與登入頁。
Route::middleware(['guest:customer', LowercaseEmail::class])->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth:customer')->name('logout');

Route::middleware('auth:customer')->group(function () {
    // 訪客會先被導向登入（可再轉往註冊），完成後回到結帳。
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/placed/{order}', [CheckoutController::class, 'placed'])->name('checkout.placed');

    Route::post('/orders/{order}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/orders/{order}/payment', [PaymentController::class, 'show'])->name('payments.show');

    Route::get('/account', [AccountController::class, 'show'])->name('account.show');
    Route::put('/account/recipient', [AccountController::class, 'updateRecipient'])->name('account.recipient.update');
    Route::get('/account/orders/{order}', [OrderController::class, 'show'])->name('account.orders.show');
    Route::post('/account/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('account.orders.cancel');
});

// 綠界呼叫的網址：付款通知（伺服器對伺服器）與付款後導回（顧客的瀏覽器），改以檢查碼驗證內容來自綠界。
// 從綠界跨站 POST 回來時瀏覽器不會帶上 SameSite=Lax 的 session cookie；若照常啟動 session，
// 回應會發出新的 session cookie 蓋掉顧客的登入狀態，因此拿掉 session、cookie 與 CSRF 相關的 middleware。
Route::withoutMiddleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
])->group(function () {
    Route::post('/payment/ecpay/notify', [EcpayController::class, 'notify'])->name('ecpay.notify');
    Route::post('/payment/ecpay/return', [EcpayController::class, 'result'])->name('ecpay.return');
});
