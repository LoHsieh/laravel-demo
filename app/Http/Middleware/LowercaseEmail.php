<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * 顧客的 Email 一律以小寫儲存與比對，註冊、登入與重設密碼時大小寫不同也視為同一個 Email。
 */
class LowercaseEmail
{
    public function handle(Request $request, Closure $next): Response
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => Str::lower(trim($request->input('email')))]);
        }

        return $next($request);
    }
}
