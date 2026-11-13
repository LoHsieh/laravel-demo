<script setup>
// 購物車頁：免運進度 → 購物車項目（可改數量、移除）→ 訂單摘要。金額與運費一律以伺服器回傳的購物車為準。
import { computed, reactive, ref } from 'vue';
import { cartRequest } from '../cart.js';
import { formatPrice } from '../format.js';

const props = defineProps({
    cart: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const cart = ref(props.cart);
const busy = reactive({});
const errors = reactive({});

const freeShipping = computed(() => cart.value.shipping_fee === 0);
const progress = computed(() => Math.min(100, Math.round((cart.value.subtotal / cart.value.free_shipping_threshold) * 100)));
const shippingMessage = computed(() => freeShipping.value
    ? '已達免運門檻，本筆訂單免運費。'
    : `再買 ${formatPrice(cart.value.free_shipping_remaining)} 即可享免運。`);

const update = async (item, request) => {
    if (busy[item.key]) return;

    busy[item.key] = true;
    delete errors[item.key];

    try {
        cart.value = await request();

        // 最後一個購物車項目被移除時，改由伺服器顯示空購物車的引導畫面。
        if (cart.value.items.length === 0) window.location.reload();
    } catch (failure) {
        errors[item.key] = failure.message;
    } finally {
        delete busy[item.key];
    }
};

const itemUrl = (item) => `${props.urls.cartItems}/${encodeURIComponent(item.key)}`;

const changeQuantity = (item, delta) => update(item, () => cartRequest('PATCH', itemUrl(item), { quantity: item.quantity + delta }));
const remove = (item) => update(item, () => cartRequest('DELETE', itemUrl(item)));
</script>

<template>
    <div class="flex flex-wrap items-start gap-14 max-sm:block">
        <div class="min-w-0 flex-[999_1_560px]">
            <div class="mb-2 bg-sand px-6 py-5 max-sm:mt-6 max-sm:px-4 max-sm:py-3.5">
                <p role="status" class="mb-3 text-sm max-sm:mb-2 max-sm:text-[13px]">{{ shippingMessage }}</p>
                <div aria-hidden="true" class="h-[3px] bg-line">
                    <div class="h-full bg-accent" :style="{ width: `${progress}%` }"></div>
                </div>
            </div>

            <ul>
                <li
                    v-for="item in cart.items"
                    :key="item.key"
                    class="flex flex-wrap items-center gap-x-7 gap-y-5 border-b border-line py-7 max-sm:flex-nowrap max-sm:items-stretch max-sm:gap-3.5 max-sm:py-5"
                    :class="{ 'opacity-60': busy[item.key] }"
                >
                    <div aria-hidden="true" class="flex h-30 flex-[0_0_96px] items-center justify-center bg-sand max-sm:h-24 max-sm:flex-[0_0_76px]">
                        <div class="relative h-[70px] w-[52px] rounded-[2px] max-sm:h-[54px] max-sm:w-10" :style="{ background: item.bag_color }">
                            <div class="absolute inset-x-0 top-0 h-2 bg-ink/12 max-sm:h-1.5"></div>
                            <div class="absolute inset-x-[9px] top-[22px] h-[30px] bg-label max-sm:inset-x-[7px] max-sm:top-[17px] max-sm:h-[23px]"></div>
                        </div>
                    </div>

                    <div class="flex min-w-0 flex-[1_1_200px] flex-col gap-1 max-sm:gap-0">
                        <div class="flex items-start justify-between gap-1">
                            <div class="flex min-w-0 flex-col gap-1 max-sm:gap-0">
                                <span class="font-latin text-[13px] tracking-[0.26em] text-muted max-sm:hidden">{{ item.origin_latin }}</span>
                                <a :href="item.url" class="font-serif text-[17px] tracking-[0.06em] text-ink no-underline max-sm:text-[15px] max-sm:leading-[1.5]">{{ item.name }}</a>
                                <span class="text-[13px] text-subtle max-sm:text-xs">
                                    {{ item.variant_label }}・研磨：{{ item.grind_label }}<span class="sm:hidden">・{{ formatPrice(item.price) }}</span>
                                </span>
                                <span class="text-[13px] text-subtle max-sm:hidden">單價 {{ formatPrice(item.price) }}</span>
                            </div>
                            <button
                                type="button"
                                :aria-label="`移除 ${item.name}（${item.variant_label}・${item.grind_label}）`"
                                :disabled="busy[item.key]"
                                class="-mt-2.5 -mr-3 flex size-11 flex-none cursor-pointer items-center justify-center text-muted sm:hidden"
                                @click="remove(item)"
                            >
                                <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M6 6l12 12M18 6L6 18"></path></svg>
                            </button>
                        </div>

                        <div class="mt-auto flex items-center justify-between pt-2 sm:hidden">
                            <div role="group" :aria-label="`${item.name} 數量`" class="flex items-center border border-outline">
                                <button type="button" aria-label="減少數量" :disabled="busy[item.key] || item.quantity <= 1" class="size-11 cursor-pointer text-base text-ink disabled:cursor-not-allowed disabled:text-muted" @click="changeQuantity(item, -1)">−</button>
                                <span aria-live="polite" class="w-7 text-center font-latin text-lg">{{ item.quantity }}</span>
                                <button type="button" aria-label="增加數量" :disabled="busy[item.key] || item.quantity >= item.stock" class="size-11 cursor-pointer text-base text-ink disabled:cursor-not-allowed disabled:text-muted" @click="changeQuantity(item, 1)">+</button>
                            </div>
                            <span class="font-latin text-[19px]">{{ formatPrice(item.line_total) }}</span>
                        </div>
                        <p v-if="errors[item.key]" class="mt-1.5 text-[11px] text-accent sm:hidden">{{ errors[item.key] }}</p>
                        <p v-else-if="item.notice" class="mt-1.5 text-[11px] text-accent sm:hidden">{{ item.notice }}</p>
                        <p v-else-if="item.quantity >= item.stock" class="mt-1.5 text-[11px] text-accent sm:hidden">僅剩 {{ item.stock }} 包，已達購買上限。</p>
                    </div>

                    <div class="flex items-center gap-7 max-sm:hidden">
                        <div role="group" :aria-label="`${item.name} 數量`" class="flex items-center border border-outline">
                            <button type="button" aria-label="減少數量" :disabled="busy[item.key] || item.quantity <= 1" class="size-11 cursor-pointer text-[17px] text-ink disabled:cursor-not-allowed disabled:text-muted" @click="changeQuantity(item, -1)">−</button>
                            <span aria-live="polite" class="w-[34px] text-center font-latin text-[19px]">{{ item.quantity }}</span>
                            <button type="button" aria-label="增加數量" :disabled="busy[item.key] || item.quantity >= item.stock" class="size-11 cursor-pointer text-[17px] text-ink disabled:cursor-not-allowed disabled:text-muted" @click="changeQuantity(item, 1)">+</button>
                        </div>
                        <span class="min-w-22 text-right font-latin text-xl">{{ formatPrice(item.line_total) }}</span>
                        <button
                            type="button"
                            :aria-label="`移除 ${item.name}（${item.variant_label}・${item.grind_label}）`"
                            :disabled="busy[item.key]"
                            class="flex size-11 cursor-pointer items-center justify-center text-muted"
                            @click="remove(item)"
                        >
                            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M6 6l12 12M18 6L6 18"></path></svg>
                        </button>
                    </div>
                    <p v-if="errors[item.key]" class="flex-[1_1_100%] text-xs text-accent max-sm:hidden">{{ errors[item.key] }}</p>
                    <p v-else-if="item.notice" class="flex-[1_1_100%] text-xs text-accent max-sm:hidden">{{ item.notice }}</p>
                    <p v-else-if="item.quantity >= item.stock" class="flex-[1_1_100%] text-xs text-accent max-sm:hidden">此規格目前僅剩 {{ item.stock }} 包，已達可購買上限。</p>
                </li>
            </ul>

            <a :href="urls.products" class="mt-6 inline-flex min-h-11 items-center text-sm tracking-[0.1em] text-ink max-sm:hidden">繼續選購</a>
        </div>

        <aside aria-label="訂單摘要" class="min-w-0 flex-[1_1_320px] border border-line bg-raised px-8 py-9 max-sm:mt-5 max-sm:border-0 max-sm:bg-transparent max-sm:p-0">
            <h2 class="mb-6 font-serif text-xl font-medium tracking-[0.12em] max-sm:sr-only">訂單摘要</h2>
            <dl class="grid grid-cols-[minmax(0,1fr)_auto] gap-y-3.5 text-sm max-sm:gap-y-1.5">
                <dt class="text-subtle">商品小計</dt>
                <dd class="font-latin text-lg max-sm:text-[17px]">{{ formatPrice(cart.subtotal) }}</dd>
                <dt class="text-subtle">運費（宅配）</dt>
                <dd class="font-latin text-lg max-sm:text-[17px]">{{ freeShipping ? '免運' : formatPrice(cart.shipping_fee) }}</dd>
            </dl>
            <div class="mt-6 flex items-baseline justify-between border-t border-line pt-5 max-sm:hidden">
                <span>總計</span>
                <span class="font-latin text-[32px]">{{ formatPrice(cart.total) }}</span>
            </div>
            <a
                :href="urls.checkout"
                aria-describedby="checkout-note"
                class="mt-7 flex min-h-14 w-full items-center justify-center bg-ink text-[15px] tracking-[0.16em] text-paper no-underline max-sm:hidden"
            >前往結帳</a>
            <ul class="mt-6 flex flex-col gap-2 text-xs text-subtle max-sm:mt-5 max-sm:gap-1">
                <li id="checkout-note">結帳功能即將開放；結帳時需登入會員，可使用信用卡付款。</li>
                <li>訂單成立後保留庫存 30 分鐘，逾時未付款將自動取消。</li>
                <li class="max-sm:hidden">研磨不另收費，出貨前才研磨。</li>
            </ul>
        </aside>
    </div>

    <!-- 手機版底部固定列：總計與前往結帳 -->
    <div class="fixed inset-x-0 bottom-0 z-10 flex items-center gap-3.5 border-t border-line bg-raised px-4 pt-3 pb-5 sm:hidden">
        <div class="flex min-w-0 flex-1 flex-col">
            <span class="text-xs text-subtle">總計（{{ freeShipping ? '免運' : '含運費' }}）</span>
            <span class="font-latin text-[26px] leading-[1.1]">{{ formatPrice(cart.total) }}</span>
        </div>
        <a
            :href="urls.checkout"
            aria-describedby="checkout-note"
            class="flex min-h-13 flex-[0_0_172px] items-center justify-center bg-ink text-[15px] tracking-[0.12em] text-paper no-underline"
        >前往結帳</a>
    </div>
</template>
