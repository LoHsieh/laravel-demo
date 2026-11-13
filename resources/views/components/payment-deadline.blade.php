@props(['order'])

@php
    // 由伺服器算好剩餘秒數再交給瀏覽器倒數，不依賴顧客電腦的時鐘是否準確
    $secondsLeft = max(0, (int) floor(now()->diffInSeconds($order->payment_due_at, false)));
@endphp

{{--
    付款期限，放在 <dl> 裡。只有待付款的訂單才有意義，其他狀態不顯示。
    倒數到零時提示付款期限已到，數秒後跳回購物車（resources/js/app.js）。
--}}
@if ($order->status === \App\Ordering\OrderStatus::PendingPayment)
    <dt class="text-subtle">付款期限</dt>
    <dd data-payment-countdown data-seconds-left="{{ $secondsLeft }}" data-expired-redirect="{{ route('cart.show') }}">
        <x-shop-time :at="$order->payment_due_at" />
        <span data-countdown-remaining class="ml-2 text-[13px] text-accent">（剩餘 <span data-countdown-clock>{{ sprintf('%02d:%02d', intdiv($secondsLeft, 60), $secondsLeft % 60) }}</span>）</span>
        <p data-payment-expired role="alert" hidden class="mt-2 bg-sand px-3 py-2 text-[13px] text-ink">
            付款期限已到，未完成付款的訂單將取消並釋出庫存。<span data-redirect-seconds>5</span> 秒後回到購物車…
        </p>
    </dd>
@endif
