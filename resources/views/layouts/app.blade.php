<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '焙日 Bairi')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500&family=Noto+Sans+TC:wght@400;500&family=Noto+Serif+TC:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper font-sans text-[15px] leading-[1.8] text-ink">
    <header class="border-b border-line">
        <div class="mx-auto flex max-w-[1200px] flex-wrap items-center justify-between gap-x-10 gap-y-2 px-[clamp(16px,4vw,48px)] py-5 max-sm:py-3">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-baseline gap-3 text-ink no-underline">
                <span class="font-serif text-[26px] font-semibold tracking-[0.14em]">焙日</span>
                <span class="font-latin text-[15px] tracking-[0.32em]">BAIRI</span>
            </a>
            <nav aria-label="主選單" class="flex flex-wrap gap-x-9 text-sm tracking-[0.1em] max-sm:order-3 max-sm:w-full">
                <a href="{{ route('products.index') }}"
                   @if (request()->routeIs('products.index')) aria-current="page" @endif
                   class="inline-flex min-h-11 items-center text-ink no-underline aria-[current=page]:underline aria-[current=page]:underline-offset-8">咖啡豆</a>
                <a href="{{ route('home') }}#flavors" class="inline-flex min-h-11 items-center text-ink no-underline">依風味找</a>
                <a href="{{ route('home') }}#about" class="inline-flex min-h-11 items-center text-ink no-underline">關於焙日</a>
            </nav>
            {{-- 手機版與 Logo 同一列靠右 --}}
            <div class="flex items-center gap-1 max-sm:order-2 max-sm:-mr-2">
                @auth('customer')
                    <a href="{{ route('account.show') }}" aria-label="會員中心"
                       @if (request()->routeIs('account.*')) aria-current="page" @endif
                       class="inline-flex min-h-11 min-w-11 items-center justify-center gap-1.5 px-2 text-sm tracking-[0.1em] text-ink no-underline">
                        <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="12" cy="8.5" r="3.5"></circle><path d="M5 20c.8-3.6 3.6-5.5 7-5.5s6.2 1.9 7 5.5"></path></svg>
                        <span class="max-sm:hidden">會員</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" aria-label="登入或註冊"
                       class="inline-flex min-h-11 min-w-11 items-center justify-center gap-1.5 px-2 text-sm tracking-[0.1em] text-ink no-underline">
                        <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="12" cy="8.5" r="3.5"></circle><path d="M5 20c.8-3.6 3.6-5.5 7-5.5s6.2 1.9 7 5.5"></path></svg>
                        <span class="max-sm:hidden">登入</span>
                    </a>
                @endauth
                {{-- 數量在加入或修改購物車後由 app.js 更新 --}}
                <a href="{{ route('cart.show') }}" data-cart-link
                   aria-label="購物車，{{ $cartCount }} 件商品"
                   @if (request()->routeIs('cart.show')) aria-current="page" @endif
                   class="inline-flex min-h-11 min-w-11 items-center justify-center gap-1.5 px-2 text-ink no-underline">
                    <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M5 8h14l-1.2 12H6.2z"></path><path d="M9 8V6.5a3 3 0 0 1 6 0V8"></path></svg>
                    <span data-cart-count class="font-latin text-base">{{ $cartCount }}</span>
                </a>
            </div>
        </div>
    </header>

    <main>
        @if (session('cart_adjustments'))
            <div role="status" class="mx-auto mt-6 max-w-[1200px] px-[clamp(16px,4vw,48px)] max-sm:px-5">
                <div class="bg-sand px-6 py-4 text-sm max-sm:px-4 max-sm:py-3 max-sm:text-[13px]">
                    <p class="mb-1">已將登入前的購物車併入你的購物車，部分數量依庫存調整：</p>
                    <ul class="list-disc pl-5 text-subtle">
                        @foreach (session('cart_adjustments') as $adjustment)
                            <li>{{ $adjustment }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="border-t border-line">
        <div class="mx-auto flex max-w-[1200px] flex-wrap items-center justify-between gap-x-10 gap-y-4 px-[clamp(16px,4vw,48px)] py-12 text-[13px] text-subtle">
            <div class="flex items-baseline gap-2.5 text-ink">
                <span class="font-serif text-lg font-semibold tracking-[0.14em]">焙日</span>
                <span class="font-latin text-[13px] tracking-[0.3em]">BAIRI</span>
            </div>
            <p>作品展示用 demo，並非真實營業的商店。</p>
            <a href="https://github.com/LoHsieh/laravel-demo" class="inline-flex min-h-11 items-center tracking-[0.1em] text-ink underline">原始碼 GitHub</a>
        </div>
    </footer>
</body>
</html>
