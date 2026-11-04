<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            <nav aria-label="主選單" class="flex flex-wrap gap-x-9 text-sm tracking-[0.1em]">
                <a href="{{ route('products.index') }}"
                   @if (request()->routeIs('products.index')) aria-current="page" @endif
                   class="inline-flex min-h-11 items-center text-ink no-underline aria-[current=page]:underline aria-[current=page]:underline-offset-8">咖啡豆</a>
                <a href="{{ route('home') }}#flavors" class="inline-flex min-h-11 items-center text-ink no-underline">依風味找</a>
                <a href="{{ route('home') }}#about" class="inline-flex min-h-11 items-center text-ink no-underline">關於焙日</a>
            </nav>
        </div>
    </header>

    <main>
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
