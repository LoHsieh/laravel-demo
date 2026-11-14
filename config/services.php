<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // 綠界全方位金流。預設為綠界公開的測試特店（https://developers.ecpay.com.tw/?p=2856），
    // 正式環境以環境變數換成自己的特店與正式網址。
    'ecpay' => [
        'merchant_id' => env('ECPAY_MERCHANT_ID', '3002607'),
        'hash_key' => env('ECPAY_HASH_KEY', 'pwFHCqoQZGmho4w6'),
        'hash_iv' => env('ECPAY_HASH_IV', 'EkRm7iFT261dpevs'),
        'checkout_url' => env('ECPAY_CHECKOUT_URL', 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
