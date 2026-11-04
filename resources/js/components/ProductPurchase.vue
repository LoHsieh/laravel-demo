<script setup>
// 詳情頁購買區：01 規格 → 02 研磨方式 → 數量。研磨方式不影響價格與庫存（ADR 0001）。
import { computed, ref } from 'vue';

const props = defineProps({
    variants: { type: Array, required: true },
    grinds: { type: Array, required: true },
});

// 剩餘數量不多於此值時提示「僅剩 N 包」。
const LOW_STOCK = 10;

const selectedVariantId = ref(props.variants.find((variant) => !variant.sold_out)?.id ?? null);
const selectedGrind = ref(props.grinds[0]?.value ?? null);
const quantity = ref(1);

const variant = computed(() => props.variants.find((option) => option.id === selectedVariantId.value) ?? null);
const grind = computed(() => props.grinds.find((option) => option.value === selectedGrind.value) ?? null);
const lowStock = computed(() => variant.value !== null && variant.value.stock <= LOW_STOCK);

const formatPrice = (price) => `NT$${price.toLocaleString('en-US')}`;

const stockText = computed(() => {
    if (props.variants.length === 0) return '尚未開賣';
    if (variant.value === null) return '已售完';

    return lowStock.value ? `僅剩 ${variant.value.stock} 包` : '現貨供應';
});

const selectVariant = (option) => {
    if (option.sold_out) return;

    selectedVariantId.value = option.id;
    quantity.value = Math.min(quantity.value, option.stock);
};

const changeQuantity = (delta) => {
    if (variant.value === null) return;

    quantity.value = Math.min(variant.value.stock, Math.max(1, quantity.value + delta));
};

const summary = computed(() => {
    if (variant.value === null) return stockText.value;

    const selection = `${variant.value.label}・${grind.value?.label ?? ''} × ${quantity.value}`;

    return lowStock.value ? `${selection}・${stockText.value}` : selection;
});
</script>

<template>
    <section aria-label="購買選項" class="flex flex-col gap-7 border border-line bg-raised px-[clamp(20px,3vw,36px)] py-8 max-sm:gap-6 max-sm:px-4.5 max-sm:py-6">
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-2 border-b border-line pb-6 max-sm:hidden">
            <p class="font-latin text-[44px] leading-none">{{ variant ? formatPrice(variant.price) : '—' }}</p>
            <p class="text-[13px]" :class="lowStock ? 'text-accent' : 'text-subtle'" aria-live="polite">{{ stockText }}</p>
        </div>

        <fieldset v-if="variants.length > 0" class="flex flex-col">
            <legend class="mb-3 text-sm tracking-[0.12em] max-sm:mb-2.5 max-sm:tracking-[0.1em]">
                <span class="mr-2.5 font-latin text-[15px] text-accent max-sm:mr-2">01</span>選擇規格
            </legend>
            <div class="grid grid-cols-3 gap-2 max-sm:gap-1.5">
                <button
                    v-for="v in variants"
                    :key="v.id"
                    type="button"
                    :disabled="v.sold_out"
                    :aria-pressed="v.sold_out ? undefined : String(v.id === selectedVariantId)"
                    class="flex min-h-16 flex-col items-center justify-center border max-sm:min-h-[58px]"
                    :class="v.sold_out
                        ? 'cursor-not-allowed border-dashed border-outline bg-sand text-muted'
                        : v.id === selectedVariantId
                            ? 'cursor-pointer border-ink bg-ink text-paper'
                            : 'cursor-pointer border-outline bg-transparent text-ink'"
                    @click="selectVariant(v)"
                >
                    <span class="font-latin text-lg leading-[1.2] max-sm:text-[17px]" :class="{ 'line-through': v.sold_out }">{{ v.label }}</span>
                    <span
                        class="text-xs max-sm:text-[11px]"
                        :class="{ 'text-subtle': !v.sold_out && v.id !== selectedVariantId }"
                    >{{ v.sold_out ? '已售完' : formatPrice(v.price) }}</span>
                </button>
            </div>
        </fieldset>

        <fieldset class="flex flex-col">
            <legend class="mb-3 text-sm tracking-[0.12em] max-sm:mb-2.5 max-sm:tracking-[0.1em]">
                <span class="mr-2.5 font-latin text-[15px] text-accent max-sm:mr-2">02</span>選擇研磨方式
            </legend>
            <div class="grid grid-cols-[repeat(auto-fit,minmax(120px,1fr))] gap-2 max-sm:grid-cols-2 max-sm:gap-1.5">
                <button
                    v-for="g in grinds"
                    :key="g.value"
                    type="button"
                    :aria-pressed="String(g.value === selectedGrind)"
                    class="flex min-h-14 cursor-pointer flex-col items-center justify-center border text-sm leading-[1.4] text-ink max-sm:min-h-13"
                    :class="g.value === selectedGrind
                        ? 'border-ink bg-label shadow-[inset_0_0_0_1px_var(--color-ink)]'
                        : 'border-outline bg-transparent'"
                    @click="selectedGrind = g.value"
                >
                    <span>{{ g.label }}</span>
                    <span class="text-[11px] text-subtle">{{ g.hint }}</span>
                </button>
            </div>
            <p class="mt-3 text-[13px] text-subtle max-sm:mt-2.5 max-sm:text-xs">研磨不另收費，價格與庫存不因研磨方式改變；我們在出貨前才依你的選擇研磨。</p>
        </fieldset>

        <div class="flex flex-wrap items-center gap-3 border-t border-line pt-6 max-sm:justify-between max-sm:border-0 max-sm:pt-0">
            <span class="text-sm tracking-[0.1em] sm:hidden">
                <span class="mr-2 font-latin text-[15px] text-accent">03</span>數量
            </span>
            <div role="group" aria-label="數量" class="flex items-center border border-outline bg-paper">
                <button
                    type="button"
                    aria-label="減少數量"
                    :disabled="variant === null || quantity <= 1"
                    class="h-[54px] w-12 cursor-pointer text-lg text-ink disabled:cursor-not-allowed disabled:text-muted max-sm:h-11 max-sm:w-11"
                    @click="changeQuantity(-1)"
                >−</button>
                <span aria-live="polite" class="w-9 text-center font-latin text-xl max-sm:w-8">{{ quantity }}</span>
                <button
                    type="button"
                    aria-label="增加數量"
                    :disabled="variant === null || quantity >= variant.stock"
                    class="h-[54px] w-12 cursor-pointer text-lg text-ink disabled:cursor-not-allowed disabled:text-muted max-sm:h-11 max-sm:w-11"
                    @click="changeQuantity(1)"
                >+</button>
            </div>
            <div class="flex flex-[1_1_200px] flex-col gap-1 max-sm:hidden">
                <button
                    type="button"
                    disabled
                    aria-describedby="add-to-cart-note"
                    class="min-h-14 cursor-not-allowed bg-ink text-[15px] tracking-[0.16em] text-paper opacity-60"
                >加入購物車</button>
            </div>
        </div>
        <p id="add-to-cart-note" class="-mt-4 text-xs text-muted max-sm:hidden">購物車即將開放。</p>
    </section>

    <!-- 手機版底部固定列：任何捲動位置都看得到價格與已選內容 -->
    <div class="fixed inset-x-0 bottom-0 z-10 flex items-center gap-3.5 border-t border-line bg-raised px-4 pt-3 pb-5 sm:hidden">
        <div class="flex min-w-0 flex-1 flex-col">
            <span class="font-latin text-[26px] leading-[1.1]">{{ variant ? formatPrice(variant.price * quantity) : '—' }}</span>
            <span class="truncate text-xs" :class="lowStock ? 'text-accent' : 'text-subtle'">{{ summary }}</span>
        </div>
        <button
            type="button"
            disabled
            aria-describedby="add-to-cart-note-phone"
            class="min-h-13 flex-[0_0_172px] cursor-not-allowed bg-ink text-[15px] tracking-[0.12em] text-paper opacity-60"
        >加入購物車</button>
        <span id="add-to-cart-note-phone" class="sr-only">購物車即將開放。</span>
    </div>
</template>
