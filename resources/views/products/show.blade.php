@extends('layouts.app')

@section('title', "{$product->name}｜焙日 Bairi")

@section('content')
    <div class="mx-auto max-w-[1200px] px-[clamp(16px,4vw,48px)] pt-8 pb-24 max-sm:px-0 max-sm:pt-0 max-sm:pb-36">
        <nav aria-label="導覽路徑" class="mb-10 flex flex-wrap gap-2 text-[13px] text-muted max-sm:hidden">
            <a href="{{ route('home') }}" class="text-muted no-underline">首頁</a><span aria-hidden="true">/</span>
            <a href="{{ route('products.index') }}" class="text-muted no-underline">咖啡豆</a><span aria-hidden="true">/</span>
            <span class="text-ink">{{ $product->name }}</span>
        </nav>

        <div class="flex flex-wrap items-start gap-16 max-sm:gap-0">
            <div class="min-w-0 flex-[1_1_460px]">
                <div class="relative flex aspect-square items-center justify-center bg-sand-deep">
                    <x-coffee-bag :color="$product->bag_color" class="w-[44%] shadow-[0_40px_70px_-36px_rgba(38,34,29,0.5)] max-sm:w-[42%]">
                        <span class="font-serif text-[clamp(16px,2vw,22px)] tracking-[0.24em]">焙日</span>
                        <span class="h-px w-[26px] bg-ink"></span>
                        <span class="font-latin text-[clamp(9px,1vw,12px)] tracking-[0.24em]">{{ $product->origin_latin }}</span>
                    </x-coffee-bag>
                    <p aria-hidden="true" class="absolute top-8 right-7 font-serif text-sm tracking-[0.5em] text-subtle [writing-mode:vertical-rl] max-sm:top-5 max-sm:right-4.5 max-sm:text-xs">{{ $product->origin }}</p>
                </div>
            </div>

            <div class="flex min-w-0 flex-[1_1_400px] flex-col gap-8 max-sm:gap-6 max-sm:px-5 max-sm:pt-7">
                <div class="flex flex-col gap-4 max-sm:gap-3">
                    <p class="font-latin text-sm tracking-[0.3em] text-accent max-sm:text-[13px]">{{ $product->origin_latin }}</p>
                    <h1 class="font-serif text-[clamp(26px,3.4vw,38px)] leading-[1.45] font-medium tracking-[0.08em]">{{ $product->name }}</h1>
                    <p class="text-subtle max-sm:text-sm">{{ $product->flavor_description }}</p>
                    @if ($product->flavorNotes->isNotEmpty())
                        <ul aria-label="風味標籤" class="flex flex-wrap gap-2">
                            @foreach ($product->flavorNotes as $flavorNote)
                                <li>
                                    <a href="{{ route('products.index', ['flavor' => [$flavorNote->name]]) }}"
                                       class="inline-flex rounded-full border border-outline px-3.5 py-0.5 text-[13px] text-subtle no-underline max-sm:text-xs">{{ $flavorNote->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <dl class="grid grid-cols-3 gap-px border border-line bg-line">
                    <div class="flex flex-col gap-1 bg-paper px-5 py-4.5 max-sm:px-3.5 max-sm:py-3">
                        <dt class="text-xs tracking-[0.12em] text-muted max-sm:text-[11px]">產地</dt>
                        <dd class="font-serif text-base max-sm:text-sm">{{ $product->origin }}</dd>
                    </div>
                    <div class="flex flex-col gap-1 bg-paper px-5 py-4.5 max-sm:px-3.5 max-sm:py-3">
                        <dt class="text-xs tracking-[0.12em] text-muted max-sm:text-[11px]">處理法</dt>
                        <dd class="font-serif text-base max-sm:text-sm">{{ $product->process }}</dd>
                    </div>
                    <div class="flex flex-col gap-1 bg-paper px-5 py-4.5 max-sm:px-3.5 max-sm:py-3">
                        <dt class="text-xs tracking-[0.12em] text-muted max-sm:text-[11px]">烘焙度</dt>
                        <dd class="flex flex-col gap-2 max-sm:gap-1">
                            <span class="font-serif text-base max-sm:text-sm">{{ $product->roast }}</span>
                            @if ($roast)
                                <span role="img" aria-label="烘焙度 {{ count(App\Catalog\Roast::cases()) }} 級中的第 {{ $roast->level() }} 級" class="flex gap-1">
                                    @foreach (App\Catalog\Roast::cases() as $step)
                                        <span @class(['h-[3px] w-4 max-sm:w-3', 'bg-ink' => $step->level() <= $roast->level(), 'bg-outline' => $step->level() > $roast->level()])></span>
                                    @endforeach
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>

                <div data-vue-component="ProductPurchase" data-props="{{ json_encode($purchase) }}">
                    <section aria-label="購買選項" class="border border-line bg-raised px-[clamp(20px,3vw,36px)] py-8">
                        <p class="text-[13px] text-subtle">正在載入規格與研磨方式…</p>
                    </section>
                </div>

                <ul class="flex flex-col gap-2 text-[13px] text-subtle max-sm:text-xs">
                    <li class="flex items-center gap-2.5">
                        <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"></path><circle cx="7" cy="17.5" r="1.6"></circle><circle cx="17" cy="17.5" r="1.6"></circle></svg>
                        宅配運費 NT$100，滿 NT$1,000 免運
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="12" cy="12" r="8"></circle><path d="M12 7.5V12l3 2"></path></svg>
                        訂單成立後保留庫存 30 分鐘，請在期限內完成付款
                    </li>
                </ul>
            </div>
        </div>

        @if ($related->isNotEmpty())
            <section aria-labelledby="related-heading" class="mt-30 border-t border-line pt-16 max-sm:mx-5 max-sm:mt-16 max-sm:pt-10">
                <div class="mb-10 flex items-baseline justify-between gap-4 max-sm:mb-6">
                    <h2 id="related-heading" class="font-serif text-[26px] font-medium tracking-[0.1em] max-sm:text-[22px]">你可能也喜歡</h2>
                    <span class="text-[13px] text-muted">共 {{ $related->count() }} 支</span>
                </div>
                <ul id="related-list" class="grid grid-cols-[repeat(auto-fill,minmax(240px,1fr))] gap-x-8 gap-y-12 max-sm:grid-cols-2 max-sm:gap-x-4 max-sm:gap-y-8">
                    @foreach ($related as $relatedProduct)
                        <li @if ($loop->index >= 4) hidden data-related-extra @endif><x-product-card :product="$relatedProduct" /></li>
                    @endforeach
                </ul>
                @if ($related->count() > 4)
                    <div class="mt-14 flex items-center gap-6 max-sm:mt-10 max-sm:gap-4">
                        <span aria-hidden="true" class="h-px flex-1 bg-line"></span>
                        <button type="button" aria-expanded="false" aria-controls="related-list" data-related-toggle
                                data-label-collapsed="查看更多（{{ $related->count() - 4 }} 支）" data-label-expanded="收合"
                                class="group inline-flex min-h-12 cursor-pointer items-center gap-2.5 border border-ink bg-transparent px-7 text-sm tracking-[0.12em] text-ink">
                            <span data-related-toggle-label>查看更多（{{ $related->count() - 4 }} 支）</span>
                            <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="group-aria-expanded:rotate-180"><path d="M5 9l7 7 7-7"></path></svg>
                        </button>
                        <span aria-hidden="true" class="h-px flex-1 bg-line"></span>
                    </div>
                @endif
            </section>
        @endif
    </div>
@endsection
