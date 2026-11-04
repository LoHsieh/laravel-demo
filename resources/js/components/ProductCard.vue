<script setup>
// 咖啡豆卡片。版面與 resources/views/components/product-card.blade.php 相同，修改時兩邊一起改。
import { formatPrice } from '../format.js';

defineProps({
    product: { type: Object, required: true },
});
</script>

<template>
    <a :href="product.url" class="flex flex-col gap-5 text-ink no-underline max-sm:gap-2.5">
        <div class="relative flex aspect-[4/5] items-center justify-center bg-sand">
            <div
                aria-hidden="true"
                class="relative aspect-[3/4] w-1/2 rounded-[2px] shadow-[0_24px_40px_-24px_rgba(38,34,29,0.45)]"
                :class="{ 'opacity-55': product.sold_out }"
                :style="{ background: product.bag_color }"
            >
                <div class="absolute inset-x-0 top-0 h-[12%] rounded-t-[2px] bg-ink/12"></div>
                <div class="absolute inset-x-[16%] top-[32%] bottom-[22%] flex flex-col items-center justify-center gap-1.5 bg-label">
                    <span class="font-serif text-[13px] tracking-[0.2em] max-sm:hidden">焙日</span>
                    <span class="h-px w-4 bg-ink max-sm:hidden"></span>
                </div>
            </div>
            <span
                v-if="product.sold_out"
                class="absolute top-3.5 left-3.5 border border-ink bg-paper px-3 text-xs tracking-[0.14em] max-sm:top-2 max-sm:left-2 max-sm:px-2 max-sm:text-[11px]"
            >已售完</span>
        </div>
        <div class="flex flex-col gap-1">
            <span class="font-latin text-[13px] tracking-[0.26em] text-muted max-sm:text-[11px]">{{ product.origin_latin }}</span>
            <h3 class="font-serif text-lg leading-normal tracking-[0.06em] max-sm:text-sm">{{ product.name }}</h3>
            <span class="text-[13px] text-subtle max-sm:hidden">{{ product.flavor_description }}</span>
            <div class="mt-2 flex items-center justify-between border-t border-line pt-3 text-[13px] max-sm:mt-0 max-sm:flex-col max-sm:items-start max-sm:border-0 max-sm:pt-0">
                <span class="text-subtle max-sm:text-xs">{{ product.roast }}・{{ product.process }}</span>
                <span
                    v-if="product.min_price !== null"
                    class="font-latin text-[17px] max-sm:text-base"
                    :class="{ 'text-subtle': product.sold_out }"
                >{{ formatPrice(product.min_price) }} 起</span>
            </div>
        </div>
    </a>
</template>
