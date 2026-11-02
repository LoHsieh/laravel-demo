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
        <div class="mx-auto max-w-[1200px] px-[clamp(16px,4vw,48px)] py-5">
            <a href="{{ route('products.index') }}" class="inline-flex items-baseline gap-3 text-ink no-underline">
                <span class="font-serif text-[26px] font-semibold tracking-[0.14em]">焙日</span>
                <span class="font-latin text-[15px] tracking-[0.32em]">BAIRI</span>
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-[1200px] px-[clamp(16px,4vw,48px)] pt-14 pb-30">
        @yield('content')
    </main>
</body>
</html>
