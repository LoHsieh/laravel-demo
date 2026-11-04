{{-- 牛皮紙袋插圖，標籤紙內容可由 slot 替換。卡片版與 resources/js/components/ProductCard.vue 相同，修改時兩邊一起改。 --}}
@props(['color'])

<div aria-hidden="true" {{ $attributes->class('relative aspect-[3/4] rounded-[2px] shadow-[0_24px_40px_-24px_rgba(38,34,29,0.45)]') }} style="background: {{ $color }}">
    <div class="absolute inset-x-0 top-0 h-[12%] rounded-t-[2px] bg-ink/12"></div>
    <div class="absolute inset-x-[16%] top-[32%] bottom-[22%] flex flex-col items-center justify-center gap-1.5 bg-label text-center">
        @if ($slot->isEmpty())
            <span class="font-serif text-[13px] tracking-[0.2em] max-sm:hidden">焙日</span>
            <span class="h-px w-4 bg-ink max-sm:hidden"></span>
        @else
            {{ $slot }}
        @endif
    </div>
</div>
