<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render 在負載平衡器終結 HTTPS，需信任其轉送的標頭才能產生 https 網址。
        $middleware->trustProxies(at: '*');

        // 後台與前台各自有登入頁：未登入的店家導向後台登入頁，已登入的店家不再看到後台登入頁。
        $inAdmin = fn (Request $request) => $request->is('admin', 'admin/*');
        $middleware->redirectGuestsTo(fn (Request $request) => $inAdmin($request) ? route('admin.login') : route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $inAdmin($request) ? route('admin.products.index') : route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
