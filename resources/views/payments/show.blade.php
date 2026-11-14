@extends('layouts.app')

@section('title', '付款結果｜焙日 Bairi')

@php
    use App\Ordering\OrderStatus;
    use App\Ordering\PaymentStatus;

    $paid = $order->status === OrderStatus::Paid;
    $failed = ! $paid && $payment?->status === PaymentStatus::Failed;
    $confirming = ! $paid && $payment?->status === PaymentStatus::Pending && $order->acceptsPayment();
@endphp

@section('content')
    <div class="mx-auto max-w-[720px] px-[clamp(16px,4vw,48px)] pt-14 pb-30 max-sm:px-5 max-sm:pt-7 max-sm:pb-20">
        <p class="font-latin text-[13px] tracking-[0.3em] text-accent uppercase">Payment</p>
        <h1 class="mt-1 mb-8 font-serif text-[34px] font-medium tracking-[0.12em] max-sm:text-[26px]">
            @if ($paid)
                付款成功
            @elseif ($failed)
                付款失敗
            @elseif ($confirming)
                付款結果確認中
            @else
                訂單{{ $order->status->label() }}
            @endif
        </h1>

        <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-8 gap-y-3 border-y border-line py-6 text-sm">
            <dt class="text-subtle">訂單編號</dt>
            <dd class="font-latin tracking-[0.08em]">{{ $order->number }}</dd>
            <dt class="text-subtle">狀態</dt>
            <dd>{{ $order->status->label() }}</dd>
            <dt class="text-subtle">總計</dt>
            <dd class="font-latin text-lg">NT${{ number_format($order->total) }}</dd>
            <x-payment-deadline :order="$order" />
        </dl>

        @if ($paid)
            <p class="mt-6 text-sm text-subtle">我們已收到你的付款，將盡快為你烘焙與出貨。</p>
        @elseif ($failed)
            <p class="mt-6 text-sm text-subtle">綠界回覆：{{ $payment->result_message }}。訂單仍為待付款，你可以在付款期限前重新付款。</p>
        @elseif ($confirming)
            <p class="mt-6 text-sm text-subtle">尚未收到綠界的付款通知，請稍候再<a href="{{ route('payments.show', $order) }}" class="text-ink">重新整理</a>。若你沒有在綠界完成付款，可以重新付款。</p>
        @endif

        {{-- 確認中也提供重新付款：顧客關掉綠界付款頁時，綠界不會送來任何通知 --}}
        @if (! $paid && $order->acceptsPayment())
            @include('payments._pay-button', ['label' => $failed || $confirming ? '重新付款' : '前往綠界付款'])
        @endif

        <a href="{{ route('products.index') }}" class="mt-8 inline-flex min-h-11 items-center text-sm tracking-[0.1em] text-ink">繼續選購</a>
    </div>
@endsection
