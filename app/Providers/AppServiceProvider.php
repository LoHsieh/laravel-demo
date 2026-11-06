<?php

namespace App\Providers;

use App\Cart\Cart;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 頁首的購物車數量，每一頁都需要。
        View::composer('layouts.app', fn ($view) => $view->with('cartCount', $this->app->make(Cart::class)->count()));
    }
}
