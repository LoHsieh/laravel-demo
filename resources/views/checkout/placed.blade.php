@extends('layouts.app')

@section('title', '訂單已成立｜焙日 Bairi')

@section('content')
    <div class="mx-auto max-w-[720px] px-[clamp(16px,4vw,48px)] pt-14 pb-30 max-sm:px-5 max-sm:pt-7 max-sm:pb-20">
        <p class="font-latin text-[13px] tracking-[0.3em] text-accent uppercase">Order placed</p>
        <h1 class="mt-1 mb-8 font-serif text-[34px] font-medium tracking-[0.12em] max-sm:text-[26px]">訂單已成立</h1>

        <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-8 gap-y-3 border-y border-line py-6 text-sm">
            <dt class="text-subtle">訂單編號</dt>
            <dd class="font-latin tracking-[0.08em]">{{ $order->number }}</dd>
            <dt class="text-subtle">狀態</dt>
            <dd>{{ $order->status->label() }}</dd>
            <dt class="text-subtle">總計</dt>
            <dd class="font-latin text-lg">NT${{ number_format($order->total) }}</dd>
            <dt class="text-subtle">付款期限</dt>
            <dd>{{ $order->payment_due_at->timezone('Asia/Taipei')->format('Y/m/d H:i') }}</dd>
        </dl>

        <p class="mt-6 text-sm text-subtle">庫存已為你保留到付款期限為止，逾期未付款將取消訂單並釋出庫存。</p>
        @if ($order->acceptsPayment())
            {{-- 剛下單時直接導向綠界；之後回到這頁（例如按上一頁）只顯示按鈕，不會自動再開一次付款 --}}
            @include('payments._pay-button', ['autoSubmit' => session('start_payment', false)])
        @endif
        <a href="{{ route('products.index') }}" class="mt-8 inline-flex min-h-11 items-center text-sm tracking-[0.1em] text-ink">繼續選購</a>
    </div>
@endsection
