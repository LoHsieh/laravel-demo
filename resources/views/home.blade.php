@extends('layouts.app')

@section('title', '焙日 Bairi｜精品咖啡豆')

@php
    $flavorShortcuts = [
        ['name' => '花香', 'latin' => 'FLORAL', 'swatch' => '#E3D6CF', 'text' => '茉莉、佛手柑般輕盈的香氣。'],
        ['name' => '果酸', 'latin' => 'FRUITY', 'swatch' => '#E2C9B4', 'text' => '莓果、柑橘般明亮的酸質。'],
        ['name' => '堅果', 'latin' => 'NUTTY', 'swatch' => '#CDB89C', 'text' => '杏仁、烤榛果般溫和圓潤。'],
        ['name' => '巧克力', 'latin' => 'CHOCOLATE', 'swatch' => '#9C8068', 'text' => '可可、黑糖般厚實的甜感。'],
    ];
@endphp

@section('content')
    <section class="mx-auto flex max-w-[1200px] flex-wrap items-center gap-16 px-[clamp(16px,4vw,48px)] py-[clamp(48px,8vw,112px)]">
        <div class="min-w-0 flex-[1_1_420px]">
            <p class="mb-7 font-latin text-[15px] tracking-[0.34em] text-accent">SMALL BATCH ROASTERY</p>
            <h1 class="font-serif text-[clamp(36px,5vw,60px)] leading-[1.4] font-medium tracking-[0.08em]">每日，<br>好好烘一支豆子。</h1>
            <p class="mt-8 max-w-[420px] text-subtle">焙日只販售單一產區的精品咖啡豆。每週小批次烘焙，下單後才依你的沖煮器具研磨。</p>
            <div class="mt-12 flex flex-wrap items-center gap-x-9 gap-y-5">
                <a href="{{ route('products.index') }}" class="inline-flex min-h-13 items-center bg-ink px-9 text-sm tracking-[0.14em] text-paper no-underline">選購咖啡豆</a>
                <a href="#flavors" class="inline-flex min-h-11 items-center border-b border-ink text-sm tracking-[0.14em] text-ink no-underline">從風味開始找</a>
            </div>
        </div>

        @if ($featured)
            <div class="min-w-0 flex-[1_1_420px]">
                <div class="relative flex aspect-[5/6] items-center justify-center bg-sand-deep">
                    <x-coffee-bag :color="$featured->bag_color" class="w-[46%] shadow-[0_40px_70px_-36px_rgba(38,34,29,0.5)]">
                        <span class="font-serif text-[clamp(16px,2vw,24px)] tracking-[0.24em]">焙日</span>
                        <span class="h-px w-7 bg-ink"></span>
                        <span class="px-1 font-latin text-[clamp(9px,1vw,12px)] tracking-[0.24em]">{{ $featured->origin_latin }}</span>
                        @if ($featuredRoast)
                            <span class="font-latin text-[clamp(8px,0.9vw,11px)] tracking-[0.2em] text-subtle">{{ $featuredRoast }}</span>
                        @endif
                    </x-coffee-bag>
                    <p aria-hidden="true" class="absolute top-9 right-8 font-serif text-sm tracking-[0.5em] text-subtle [writing-mode:vertical-rl]">本週精選</p>
                    <p class="absolute bottom-7 left-8 right-8 font-serif text-sm tracking-[0.12em] text-subtle">
                        <span class="sr-only">本週精選：</span>{{ $featured->name }}
                    </p>
                </div>
            </div>
        @endif
    </section>

    <section id="flavors" class="border-t border-line">
        <div class="mx-auto max-w-[1200px] px-[clamp(16px,4vw,48px)] py-24 max-sm:py-16">
            <div class="mb-12 flex flex-wrap items-end justify-between gap-x-10 gap-y-4">
                <div>
                    <p class="mb-3 font-latin text-sm tracking-[0.32em] text-accent">FIND BY FLAVOR</p>
                    <h2 class="font-serif text-[32px] font-medium tracking-[0.1em] max-sm:text-[26px]">從風味開始找</h2>
                </div>
                <p class="max-w-[360px] text-sm text-subtle">不熟悉產區也沒關係，選一個喜歡的味道，我們幫你找到對的豆子。</p>
            </div>
            <div class="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-px border border-line bg-line max-sm:grid-cols-2">
                @foreach ($flavorShortcuts as $flavor)
                    <a href="{{ route('products.index', ['flavor' => [$flavor['name']]]) }}"
                       class="flex flex-col gap-3.5 bg-paper px-8 pt-9 pb-10 text-ink no-underline hover:bg-raised max-sm:px-5 max-sm:pt-6 max-sm:pb-7">
                        <span aria-hidden="true" class="size-10 rounded-full" style="background: {{ $flavor['swatch'] }}"></span>
                        <span class="font-serif text-[22px] tracking-[0.12em] max-sm:text-lg">{{ $flavor['name'] }}</span>
                        <span class="font-latin text-[13px] tracking-[0.26em] text-muted">{{ $flavor['latin'] }}</span>
                        <span class="text-sm text-subtle max-sm:text-[13px]">{{ $flavor['text'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section id="beans" class="border-t border-line">
        <div class="mx-auto max-w-[1200px] px-[clamp(16px,4vw,48px)] py-24 max-sm:py-16">
            <div class="mb-12 flex flex-wrap items-end justify-between gap-x-10 gap-y-4">
                <div>
                    <p class="mb-3 font-latin text-sm tracking-[0.32em] text-accent">THIS WEEK</p>
                    <h2 class="font-serif text-[32px] font-medium tracking-[0.1em] max-sm:text-[26px]">本週烘焙</h2>
                </div>
                <div class="flex items-center gap-7">
                    <a href="{{ route('products.index') }}" class="inline-flex min-h-11 items-center border-b border-ink text-sm tracking-[0.12em] text-ink no-underline">查看全部咖啡豆</a>
                    <div class="flex gap-2 max-sm:hidden">
                        <button type="button" data-rail-scroll="-1" aria-controls="latest-rail" aria-label="上一支咖啡豆" class="rail-button">
                            <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M15 5l-7 7 7 7"/></svg>
                        </button>
                        <button type="button" data-rail-scroll="1" aria-controls="latest-rail" aria-label="下一支咖啡豆" class="rail-button">
                            <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <ul id="latest-rail" class="rail" aria-label="最新上架的咖啡豆">
                @foreach ($latestProducts as $product)
                    <li><x-product-card :product="$product" /></li>
                @endforeach
            </ul>
        </div>
    </section>

    <section id="about" class="bg-sand">
        <div class="mx-auto grid max-w-[1200px] grid-cols-[repeat(auto-fit,minmax(260px,1fr))] gap-x-16 gap-y-12 px-[clamp(16px,4vw,48px)] py-24 max-sm:py-16">
            @foreach ([
                ['01', '小批次烘焙', '每支豆子一次只烘少量，讓你收到的咖啡豆都在最好喝的時候。'],
                ['02', '下單後才研磨', '選擇你的沖煮器具，我們在出貨前才研磨。研磨不另收費。'],
                ['03', '滿 NT$1,000 免運', '宅配運費 NT$100，單筆訂單商品金額滿 NT$1,000 即免運費。'],
            ] as [$number, $title, $text])
                <div class="flex flex-col gap-4">
                    <span class="font-latin text-[40px] leading-none text-accent">{{ $number }}</span>
                    <h3 class="font-serif text-xl font-medium tracking-[0.12em]">{{ $title }}</h3>
                    <p class="text-sm text-subtle">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endsection
