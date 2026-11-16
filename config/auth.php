<?php

use App\Models\Admin;
use App\Models\Customer;

/*
| 顧客與店家是兩張資料表、兩個 guard。前台預設使用 customer guard，
| 後台（/admin）使用 admin guard。店家沒有註冊與忘記密碼的流程，所以沒有 admins 的 password broker。
*/
return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'customer'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'customers'),
    ],

    'guards' => [
        'customer' => [
            'driver' => 'session',
            'provider' => 'customers',
        ],

        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
    ],

    'providers' => [
        'customers' => [
            'driver' => 'eloquent',
            'model' => Customer::class,
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model' => Admin::class,
        ],
    ],

    'passwords' => [
        'customers' => [
            'provider' => 'customers',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
