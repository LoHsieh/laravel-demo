@extends('layouts.app')

@section('title', '結帳｜焙日 Bairi')

@section('content')
    <div class="mx-auto max-w-[1200px] px-[clamp(16px,4vw,48px)] pt-14 pb-30 max-sm:px-5 max-sm:pt-7 max-sm:pb-20">
        <h1 class="mb-10 font-serif text-[34px] font-medium tracking-[0.12em] max-sm:mb-6 max-sm:text-[26px]">結帳</h1>

        @if ($errors->has('checkout'))
            <div role="alert" class="mb-8 bg-sand px-5 py-4 text-sm text-ink">
                @foreach ($errors->get('checkout') as $message)
                    <p @class(['mt-1 text-accent' => ! $loop->first])>{{ $message }}</p>
                @endforeach
                <a href="{{ route('cart.show') }}" class="mt-2 inline-flex min-h-11 items-center text-ink underline underline-offset-4">回到購物車調整</a>
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.store') }}" novalidate class="flex flex-wrap items-start gap-14 max-sm:block">
            @csrf

            <section aria-labelledby="recipient-heading" class="min-w-0 flex-[999_1_480px]">
                <h2 id="recipient-heading" class="font-serif text-[26px] font-medium tracking-[0.1em]">
                    <span class="mr-2 font-latin text-accent">01</span>收件資訊
                </h2>
                <p class="mt-1 mb-7 text-[13px] text-subtle">只提供宅配。已帶入會員中心的預設收件資訊，可在這裡修改。</p>

                <x-form-field name="recipient_name" label="收件人姓名" :value="$customer->recipient_name" autocomplete="name" required />
                <x-form-field name="recipient_phone" label="收件人電話" type="tel" :value="$customer->recipient_phone" autocomplete="tel" required />
                <x-form-field name="recipient_address" label="收件地址" :value="$customer->recipient_address" autocomplete="street-address" required />
            </section>

            <aside aria-labelledby="summary-heading" class="min-w-0 flex-[1_1_360px] border border-line bg-raised px-8 py-9 max-sm:mt-8 max-sm:border-x-0 max-sm:bg-transparent max-sm:px-0">
                <h2 id="summary-heading" class="mb-6 font-serif text-[26px] font-medium tracking-[0.1em]">
                    <span class="mr-2 font-latin text-accent">02</span>訂單內容
                </h2>

                <ul class="mb-6 flex flex-col border-b border-line">
                    @foreach ($summary->items as $item)
                        <li class="flex items-start justify-between gap-4 border-t border-line py-4">
                            <div class="flex min-w-0 flex-col gap-0.5">
                                <span class="font-serif text-[15px] tracking-[0.06em]">{{ $item->variant->product->name }}</span>
                                <span class="text-[13px] text-subtle">{{ $item->variant->label }}・{{ $item->grind->label() }}</span>
                                <span class="text-[13px] text-subtle">NT${{ number_format($item->variant->price) }} × {{ $item->quantity }}</span>
                            </div>
                            <span class="flex-none font-latin text-[17px]">NT${{ number_format($item->lineTotal()) }}</span>
                        </li>
                    @endforeach
                </ul>

                <dl class="grid grid-cols-[minmax(0,1fr)_auto] gap-y-3 text-sm">
                    <dt class="text-subtle">商品小計</dt>
                    <dd class="font-latin text-lg">NT${{ number_format($summary->subtotal) }}</dd>
                    <dt class="text-subtle">運費（宅配）</dt>
                    <dd class="font-latin text-lg">{{ $summary->shippingFee === 0 ? '免運' : 'NT$'.number_format($summary->shippingFee) }}</dd>
                </dl>
                <div class="mt-6 flex items-baseline justify-between border-t border-line pt-5">
                    <span class="text-sm">總計</span>
                    <span class="font-latin text-[32px]">NT${{ number_format($summary->total()) }}</span>
                </div>

                <input type="hidden" name="expected_total" value="{{ $summary->total() }}">
                <button type="submit" class="mt-7 flex min-h-14 w-full cursor-pointer items-center justify-center bg-ink text-[15px] tracking-[0.16em] text-paper">確認下單</button>
                <ul class="mt-6 flex flex-col gap-2 text-xs text-subtle">
                    <li>送出後立即為你保留庫存，請在 {{ \App\Ordering\PlaceOrder::PAYMENT_WINDOW_MINUTES }} 分鐘內完成付款，逾期未付款將取消訂單。</li>
                    <li>研磨不另收費，出貨前才研磨。</li>
                </ul>
                <a href="{{ route('cart.show') }}" class="mt-4 inline-flex min-h-11 items-center text-sm tracking-[0.1em] text-ink">回到購物車</a>
            </aside>
        </form>
    </div>
@endsection
