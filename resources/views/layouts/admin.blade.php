<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>@yield('title', '後台')｜焙日 Bairi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500&family=Noto+Sans+TC:wght@400;500&family=Noto+Serif+TC:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
{{-- 後台沿用前台的色彩與字體（docs/design/README.md），版面以表格與表單為主 --}}
<body class="min-h-screen bg-paper font-sans text-[15px] leading-[1.8] text-ink">
    <header class="border-b border-line">
        <div class="mx-auto flex max-w-[1200px] flex-wrap items-center justify-between gap-x-10 gap-y-2 px-[clamp(16px,4vw,48px)] py-4">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-baseline gap-3 text-ink no-underline">
                <span class="font-serif text-[22px] font-semibold tracking-[0.14em]">焙日</span>
                <span class="font-latin text-[13px] tracking-[0.32em] text-accent">ADMIN</span>
            </a>
            @auth('admin')
                <nav aria-label="後台選單" class="flex flex-wrap items-center gap-x-8 text-sm tracking-[0.1em]">
                    <a href="{{ route('admin.dashboard') }}"
                       @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif
                       class="inline-flex min-h-11 items-center text-ink no-underline aria-[current=page]:underline aria-[current=page]:underline-offset-8">概況</a>
                    <a href="{{ route('admin.products.index') }}"
                       @if (request()->routeIs('admin.products.*')) aria-current="page" @endif
                       class="inline-flex min-h-11 items-center text-ink no-underline aria-[current=page]:underline aria-[current=page]:underline-offset-8">咖啡豆</a>
                    <a href="{{ route('admin.flavor-notes.index') }}"
                       @if (request()->routeIs('admin.flavor-notes.*')) aria-current="page" @endif
                       class="inline-flex min-h-11 items-center text-ink no-underline aria-[current=page]:underline aria-[current=page]:underline-offset-8">風味標籤</a>
                    <a href="{{ route('admin.orders.index') }}"
                       @if (request()->routeIs('admin.orders.*')) aria-current="page" @endif
                       class="inline-flex min-h-11 items-center text-ink no-underline aria-[current=page]:underline aria-[current=page]:underline-offset-8">訂單</a>
                    <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center text-subtle no-underline">前台</a>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex min-h-11 items-center text-subtle underline underline-offset-4">登出</button>
                    </form>
                </nav>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-[1200px] px-[clamp(16px,4vw,48px)] pt-10 pb-24 max-sm:pt-6">
        @if (session('status'))
            <p role="status" class="mb-6 bg-sand px-4 py-3 text-sm">{{ session('status') }}</p>
        @endif
        @yield('content')
    </main>
</body>
</html>
