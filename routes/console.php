<?php

use App\Ordering\CompleteShippedOrders;
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

// 出貨滿 7 天自動完成。規格只要求每日處理，但網站休眠時排程不會執行（ADR 0002），
// 固定在每天某個時間執行很可能每次都落在休眠中，因此每小時執行，醒著的任一小時都會補處理。
Schedule::call(fn (CompleteShippedOrders $completeShippedOrders) => $completeShippedOrders())
    ->name('complete-shipped-orders')
    ->hourly()
    ->withoutOverlapping();
