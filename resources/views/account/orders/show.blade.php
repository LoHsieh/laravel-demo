@extends('layouts.app')

@section('title', "訂單 {$order->number}｜焙日 Bairi")

@section('content')
    <div class="mx-auto max-w-[1200px] px-[clamp(16px,4vw,48px)] pt-14 pb-30 max-sm:px-5 max-sm:pt-7 max-sm:pb-20">
        <a href="{{ route('account.show') }}" class="inline-flex min-h-11 items-center text-sm tracking-[0.1em] text-ink">← 會員中心</a>
        <p class="mt-4 font-latin text-[13px] tracking-[0.3em] text-accent uppercase">Order</p>
        <h1 class="mt-1 mb-8 font-serif text-[34px] font-medium tracking-[0.12em] max-sm:text-[26px]">
            訂單 <span class="font-latin tracking-[0.08em]">{{ $order->number }}</span>
        </h1>

        @if (session('order_status'))
            <p role="status" class="mb-8 bg-sand px-5 py-4 text-sm text-ink">{{ session('order_status') }}</p>
        @endif
        @if ($errors->has('order'))
            <p role="alert" class="mb-8 bg-sand px-5 py-4 text-sm text-ink">{{ $errors->first('order') }}</p>
        @endif

        <div class="flex flex-wrap items-start gap-14 max-sm:block">
            <section aria-labelledby="status-heading" class="min-w-0 flex-[999_1_480px]">
                <h2 id="status-heading" class="font-serif text-[22px] font-medium tracking-[0.1em]">
                    <span class="mr-2 font-latin text-accent">01</span>訂單狀態
                </h2>
                <dl class="mt-6 grid grid-cols-[auto_minmax(0,1fr)] gap-x-8 gap-y-3 border-y border-line py-6 text-sm">
                    <dt class="text-subtle">狀態</dt>
                    <dd>{{ $order->status->label() }}</dd>
                    <dt class="text-subtle">下單時間</dt>
                    <dd>{{ $order->created_at->timezone('Asia/Taipei')->format('Y/m/d H:i') }}</dd>
                    <dt class="text-subtle">付款期限</dt>
                    <dd>{{ $order->payment_due_at->timezone('Asia/Taipei')->format('Y/m/d H:i') }}</dd>
                </dl>

                @if ($order->status === \App\Ordering\OrderStatus::PendingPayment)
                    <form method="POST" action="{{ route('account.orders.cancel', $order) }}" class="mt-6"
                          onsubmit="return confirm('確定要取消這筆訂單嗎？取消後庫存保留會釋出，無法復原。')">
                        @csrf
                        <button type="submit" class="inline-flex min-h-11 cursor-pointer items-center border border-ink px-6 text-sm tracking-[0.1em] text-ink">取消訂單</button>
                    </form>
                    <p class="mt-3 text-[13px] text-subtle">庫存已為你保留到付款期限為止，逾期未付款將取消訂單並釋出庫存。</p>
                @endif

                <h2 id="recipient-heading" class="mt-12 font-serif text-[22px] font-medium tracking-[0.1em]">
                    <span class="mr-2 font-latin text-accent">02</span>收件資訊
                </h2>
                <dl class="mt-6 grid grid-cols-[auto_minmax(0,1fr)] gap-x-8 gap-y-3 border-y border-line py-6 text-sm">
                    <dt class="text-subtle">收件人</dt>
                    <dd>{{ $order->recipient_name }}</dd>
                    <dt class="text-subtle">電話</dt>
                    <dd>{{ $order->recipient_phone }}</dd>
                    <dt class="text-subtle">地址</dt>
                    <dd>{{ $order->recipient_address }}</dd>
                    <dt class="text-subtle">配送方式</dt>
                    <dd>宅配</dd>
                </dl>
            </section>

            <aside aria-labelledby="items-heading" class="min-w-0 flex-[1_1_360px] border border-line bg-raised px-8 py-9 max-sm:mt-12 max-sm:border-x-0 max-sm:bg-transparent max-sm:px-0">
                <h2 id="items-heading" class="mb-6 font-serif text-[22px] font-medium tracking-[0.1em]">
                    <span class="mr-2 font-latin text-accent">03</span>訂單內容
                </h2>

                <ul class="mb-6 flex flex-col border-b border-line">
                    @foreach ($order->items as $item)
                        <li class="flex items-start justify-between gap-4 border-t border-line py-4">
                            <div class="flex min-w-0 flex-col gap-0.5">
                                <span class="font-serif text-[15px] tracking-[0.06em]">{{ $item->variant->product->name }}</span>
                                <span class="text-[13px] text-subtle">{{ $item->variant->label }}・{{ $item->grind->label() }}</span>
                                <span class="text-[13px] text-subtle">NT${{ number_format($item->unit_price) }} × {{ $item->quantity }}</span>
                            </div>
                            <span class="flex-none font-latin text-[17px]">NT${{ number_format($item->lineTotal()) }}</span>
                        </li>
                    @endforeach
                </ul>

                <dl class="grid grid-cols-[minmax(0,1fr)_auto] gap-y-3 text-sm">
                    <dt class="text-subtle">商品小計</dt>
                    <dd class="font-latin text-lg">NT${{ number_format($order->subtotal) }}</dd>
                    <dt class="text-subtle">運費（宅配）</dt>
                    <dd class="font-latin text-lg">{{ $order->shipping_fee === 0 ? '免運' : 'NT$'.number_format($order->shipping_fee) }}</dd>
                </dl>
                <div class="mt-6 flex items-baseline justify-between border-t border-line pt-5">
                    <span class="text-sm">總計</span>
                    <span class="font-latin text-[32px]">NT${{ number_format($order->total) }}</span>
                </div>
            </aside>
        </div>
    </div>
@endsection
