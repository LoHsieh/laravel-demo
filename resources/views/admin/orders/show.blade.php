@extends('layouts.admin')

@section('title', "訂單 {$order->number}")

@section('content')
    <a href="{{ route('admin.orders.index') }}" class="inline-flex min-h-11 items-center text-sm tracking-[0.1em] text-ink">← 訂單</a>
    <h1 class="mt-2 mb-8 font-serif text-[30px] font-medium tracking-[0.1em]">
        訂單 <span class="font-latin tracking-[0.08em]">{{ $order->number }}</span>
    </h1>

    @if ($order->status === \App\Ordering\OrderStatus::PaymentException)
        <p role="alert" class="mb-8 bg-sand px-5 py-4 text-sm">
            這筆訂單取消後才收到付款成功，庫存已經歸還，請聯絡顧客（{{ $order->customer->email }}）處理退款。
        </p>
    @endif

    <div class="flex flex-wrap items-start gap-14 max-sm:block">
        <section aria-labelledby="status-heading" class="min-w-0 flex-[999_1_480px]">
            <h2 id="status-heading" class="font-serif text-[22px] font-medium tracking-[0.1em]">訂單狀態</h2>
            <dl class="mt-4 grid grid-cols-[auto_minmax(0,1fr)] gap-x-8 gap-y-3 border-y border-line py-6 text-sm">
                <dt class="text-subtle">狀態</dt>
                <dd>{{ $order->status->label() }}</dd>
                <dt class="text-subtle">下單時間</dt>
                <dd><x-shop-time :at="$order->created_at" /></dd>
                @if ($order->shipped_at)
                    <dt class="text-subtle">出貨時間</dt>
                    <dd>
                        <x-shop-time :at="$order->shipped_at" />
                        @if ($order->status === \App\Ordering\OrderStatus::Shipped)
                            （出貨滿 {{ \App\Models\Order::AUTO_COMPLETE_AFTER_DAYS }} 天自動完成）
                        @endif
                    </dd>
                @endif
                <dt class="text-subtle">顧客</dt>
                <dd>{{ $order->customer->email }}</dd>
            </dl>

            @if ($errors->has('status'))
                <p role="alert" class="mt-6 bg-sand px-5 py-4 text-sm">{{ $errors->first('status') }}</p>
            @endif

            @foreach ($nextStatuses as $next)
                <form method="POST" action="{{ route('admin.orders.status.update', $order) }}" class="mt-6"
                      onsubmit="return confirm('確定要把訂單改為{{ $next->label() }}嗎？')">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $next->value }}">
                    <button type="submit" class="inline-flex min-h-12 cursor-pointer items-center bg-ink px-6 text-sm tracking-[0.14em] text-paper">改為{{ $next->label() }}</button>
                </form>
            @endforeach

            <h2 id="recipient-heading" class="mt-10 font-serif text-[22px] font-medium tracking-[0.1em]">收件資訊</h2>
            <dl class="mt-4 grid grid-cols-[auto_minmax(0,1fr)] gap-x-8 gap-y-3 border-y border-line py-6 text-sm">
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

        <section aria-labelledby="items-heading" class="min-w-0 flex-[1_1_360px] border border-line bg-raised px-8 py-8 max-sm:mt-10 max-sm:border-x-0 max-sm:bg-transparent max-sm:px-0">
            <h2 id="items-heading" class="mb-4 font-serif text-[22px] font-medium tracking-[0.1em]">訂單項目</h2>

            <ul class="mb-6 flex flex-col border-b border-line">
                @foreach ($order->items as $item)
                    <li class="flex items-start justify-between gap-4 border-t border-line py-4">
                        <div class="flex min-w-0 flex-col gap-0.5">
                            <span class="font-serif text-[15px] tracking-[0.06em]">{{ $item->product_name }}</span>
                            <span class="text-[13px]">{{ $item->label }}・{{ $item->grind->label() }}・× {{ $item->quantity }}</span>
                            <span class="text-[13px] text-subtle">NT${{ number_format($item->unit_price) }}</span>
                        </div>
                        <span class="flex-none font-latin">NT${{ number_format($item->lineTotal()) }}</span>
                    </li>
                @endforeach
            </ul>

            <dl class="grid grid-cols-[minmax(0,1fr)_auto] gap-y-2 text-sm">
                <dt class="text-subtle">商品小計</dt>
                <dd class="font-latin">NT${{ number_format($order->subtotal) }}</dd>
                <dt class="text-subtle">運費</dt>
                <dd class="font-latin">{{ $order->shipping_fee === 0 ? '免運' : 'NT$'.number_format($order->shipping_fee) }}</dd>
                <dt>總計</dt>
                <dd class="font-latin text-lg">NT${{ number_format($order->total) }}</dd>
            </dl>
        </section>
    </div>
@endsection
