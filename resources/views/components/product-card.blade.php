{{-- 咖啡豆卡片。版面與 resources/js/components/ProductCard.vue 相同，修改時兩邊一起改。 --}}
@props(['product'])

@php($soldOut = ! $product->has_stock)

<a href="{{ route('products.show', $product) }}" {{ $attributes->class('flex flex-col gap-5 text-ink no-underline max-sm:gap-2.5') }}>
    <div class="relative flex aspect-[4/5] items-center justify-center bg-sand">
        <x-coffee-bag :color="$product->bag_color" @class(['w-1/2', 'opacity-55' => $soldOut]) />
        @if ($soldOut)
            <span class="absolute top-3.5 left-3.5 border border-ink bg-paper px-3 text-xs tracking-[0.14em] max-sm:top-2 max-sm:left-2 max-sm:px-2 max-sm:text-[11px]">已售完</span>
        @endif
    </div>
    <div class="flex flex-col gap-1">
        <span class="font-latin text-[13px] tracking-[0.26em] text-muted max-sm:text-[11px]">{{ $product->origin_latin }}</span>
        <h3 class="font-serif text-lg leading-normal tracking-[0.06em] max-sm:text-sm">{{ $product->name }}</h3>
        <span class="text-[13px] text-subtle max-sm:hidden">{{ $product->flavor_description }}</span>
        <div class="mt-2 flex items-center justify-between border-t border-line pt-3 text-[13px] max-sm:mt-0 max-sm:flex-col max-sm:items-start max-sm:border-0 max-sm:pt-0">
            <span class="text-subtle max-sm:text-xs">{{ $product->roast }}・{{ $product->process }}</span>
            @if ($product->variants_min_price !== null)
                <span @class(['font-latin text-[17px] max-sm:text-base', 'text-subtle' => $soldOut])>NT${{ number_format($product->variants_min_price) }} 起</span>
            @endif
        </div>
    </div>
</a>
