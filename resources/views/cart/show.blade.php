@extends('layouts.app')

@section('title', '購物車｜焙日 Bairi')

@section('content')
    <div class="mx-auto max-w-[1200px] px-[clamp(16px,4vw,48px)] pt-14 pb-30 max-sm:px-5 max-sm:pt-7 max-sm:pb-40">
        <div class="mb-3 flex flex-wrap items-baseline gap-x-5 gap-y-2 max-sm:mb-1.5 max-sm:gap-x-3">
            <h1 class="font-serif text-[34px] font-medium tracking-[0.12em] max-sm:text-[26px]">購物車</h1>
            @if ($cart['count'] > 0)
                <span class="text-[13px] text-muted">共 <span data-cart-count>{{ $cart['count'] }}</span> 件</span>
            @endif
        </div>
        <p class="mb-12 text-[13px] text-subtle max-sm:mb-0 max-sm:text-xs">你目前以訪客身分瀏覽。登入後，購物車內容會自動併入你的會員購物車。</p>

        @if ($cart['items'] === [])
            <div class="flex flex-col items-center gap-5 border-y border-line px-6 py-24 text-center max-sm:mt-8 max-sm:gap-3.5 max-sm:px-4 max-sm:py-16">
                <svg aria-hidden="true" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.9" class="text-muted max-sm:size-12"><path d="M5 8h14l-1.2 12H6.2z"></path><path d="M9 8V6.5a3 3 0 0 1 6 0V8"></path></svg>
                <h2 class="font-serif text-[22px] font-medium tracking-[0.12em] max-sm:text-[19px]">購物車還是空的</h2>
                <p class="text-sm text-subtle">去看看這週烘了哪些豆子吧。</p>
                <a href="{{ route('products.index') }}" class="mt-3 inline-flex min-h-13 items-center bg-ink px-9 text-sm tracking-[0.14em] text-paper no-underline max-sm:mt-2">選購咖啡豆</a>
            </div>
        @else
            <div data-vue-component="CartPage" data-props="{{ json_encode(['cart' => $cart, 'urls' => $urls]) }}">
                <p class="text-[13px] text-subtle max-sm:mt-6">正在載入購物車…</p>
            </div>
        @endif
    </div>
@endsection
