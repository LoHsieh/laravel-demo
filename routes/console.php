<?php

use App\Ordering\ExpireOverdueOrders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 付款逾期。在排程程序內直接執行（不另開 artisan 行程），休眠醒來後的第一分鐘就會補處理。
Schedule::call(fn (ExpireOverdueOrders $expireOverdueOrders) => $expireOverdueOrders())
    ->name('expire-overdue-orders')
    ->everyMinute()
    ->withoutOverlapping();
