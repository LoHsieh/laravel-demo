<?php

namespace App\Providers;

use App\Cart\Cart;
use App\Cart\CustomerCartStore;
use App\Cart\MergeGuestCart;
use App\Cart\SessionCartStore;
use App\Payment\Ecpay\CheckMacValue;
use App\Payment\Ecpay\EcpayGateway;
use App\Payment\PaymentGateway;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 登入的顧客用資料庫裡的購物車，訪客用 session 裡的。每次解析時依當下的登入狀態決定。
        $this->app->bind(Cart::class, function ($app) {
            $customer = Auth::guard('customer')->user();

            return new Cart($customer === null
                ? $app->make(SessionCartStore::class)
                : new CustomerCartStore($customer));
        });

        $this->app->bind(PaymentGateway::class, fn () => new EcpayGateway(
            merchantId: config('services.ecpay.merchant_id'),
            checkMac: new CheckMacValue(config('services.ecpay.hash_key'), config('services.ecpay.hash_iv')),
            checkoutUrl: config('services.ecpay.checkout_url'),
            notifyUrl: route('ecpay.notify'),
            resultUrl: route('ecpay.return'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, MergeGuestCart::class);

        // 頁首的購物車數量，每一頁都需要。
        View::composer('layouts.app', fn ($view) => $view->with('cartCount', $this->app->make(Cart::class)->count()));
    }
}
