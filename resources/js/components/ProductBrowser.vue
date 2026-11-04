<script setup>
// 咖啡豆列表：篩選、搜尋、排序、分頁都以同一個網址向後端取得 JSON，
// 並把條件寫回網址，重新整理或分享後條件仍在。
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import ProductCard from './ProductCard.vue';

const props = defineProps({
    filters: { type: Object, required: true },
    results: { type: Object, required: true },
    facets: { type: Array, required: true },
    sortOptions: { type: Array, required: true },
});

const GROUP_KEYS = props.facets.map((group) => group.key);
const DEFAULT_SORT = props.sortOptions[0].value;
const SEARCH_DELAY_MS = 350;

const filters = reactive(structuredClone(props.filters));
const results = ref(props.results);
const keyword = ref(filters.q);
const loading = ref(false);
const failed = ref(false);
const sheetOpen = ref(false);
const resultsTop = ref(null);
const sheetCloseButton = ref(null);

const total = computed(() => results.value.pagination.total);
const currentPage = computed(() => results.value.pagination.current_page);
const lastPage = computed(() => results.value.pagination.last_page);

const chips = computed(() => {
    const list = GROUP_KEYS.flatMap((key) =>
        filters[key].map((value) => ({
            id: `${key}:${value}`,
            label: value,
            aria: `移除篩選：${value}`,
            remove: () => toggle(key, value),
        })),
    );

    if (filters.q) {
        list.push({ id: 'q', label: `「${filters.q}」`, aria: '清除搜尋', remove: clearKeyword });
    }

    return list;
});

const activeFilterCount = computed(() => GROUP_KEYS.reduce((count, key) => count + filters[key].length, 0));

/** 頁碼：頭尾、目前頁與前後各一頁，中間以省略號表示。 */
const pageItems = computed(() => {
    const pages = [...new Set([1, currentPage.value - 1, currentPage.value, currentPage.value + 1, lastPage.value])]
        .filter((page) => page >= 1 && page <= lastPage.value)
        .sort((a, b) => a - b);

    return pages.flatMap((page, index) => (index > 0 && page - pages[index - 1] > 1 ? ['…', page] : [page]));
});

function toSearchParams(page) {
    const params = new URLSearchParams();

    GROUP_KEYS.forEach((key) => filters[key].forEach((value) => params.append(`${key}[]`, value)));
    if (filters.q) params.set('q', filters.q);
    if (filters.sort !== DEFAULT_SORT) params.set('sort', filters.sort);
    if (page > 1) params.set('page', String(page));

    return params;
}

function readUrl() {
    const params = new URLSearchParams(window.location.search);

    GROUP_KEYS.forEach((key) => {
        filters[key] = params.getAll(`${key}[]`);
    });
    filters.q = (params.get('q') ?? '').trim();
    filters.sort = props.sortOptions.some((option) => option.value === params.get('sort')) ? params.get('sort') : DEFAULT_SORT;
    keyword.value = filters.q;

    return Number(params.get('page')) || 1;
}

let controller = null;
let lastChange = null;

/**
 * @param {number} page
 * @param {{ history?: 'push' | 'replace' | 'none', source?: string }} options
 */
async function load(page, { history = 'push', source = null } = {}) {
    const query = toSearchParams(page).toString();
    const url = `${window.location.pathname}${query ? `?${query}` : ''}`;

    // 連續輸入關鍵字只留下一筆瀏覽紀錄。
    if (history === 'push' && source !== null && source === lastChange) history = 'replace';
    if (history === 'push') window.history.pushState(null, '', url);
    if (history === 'replace') window.history.replaceState(null, '', url);
    lastChange = source;

    controller?.abort();
    const current = (controller = new AbortController());
    loading.value = true;
    failed.value = false;

    try {
        // no-store：避免瀏覽器把 JSON 存成這個網址的快取，返回上一頁時誤顯示 JSON。
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
            signal: current.signal,
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        results.value = await response.json();
    } catch (error) {
        if (error.name !== 'AbortError') failed.value = true;
    } finally {
        if (controller === current) loading.value = false;
    }
}

function toggle(key, value) {
    const values = filters[key];
    filters[key] = values.includes(value) ? values.filter((item) => item !== value) : [...values, value];
    // 在手機篩選面板裡連續點選只留下一筆瀏覽紀錄，返回上一頁一次就能還原。
    load(1, { source: sheetOpen.value ? 'sheet' : null });
}

function changeSort(event) {
    filters.sort = event.target.value;
    load(1);
}

let searchTimer = null;

function search() {
    clearTimeout(searchTimer);
    const next = keyword.value.trim();
    if (next === filters.q) return;
    filters.q = next;
    load(1, { source: 'search' });
}

watch(keyword, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(search, SEARCH_DELAY_MS);
});

function clearKeyword() {
    clearTimeout(searchTimer);
    keyword.value = '';
    filters.q = '';
    load(1);
}

function clearAll() {
    clearTimeout(searchTimer);
    GROUP_KEYS.forEach((key) => {
        filters[key] = [];
    });
    keyword.value = '';
    filters.q = '';
    load(1);
}

async function goToPage(page) {
    await load(page);
    resultsTop.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function onPopState() {
    clearTimeout(searchTimer);
    lastChange = null;
    load(readUrl(), { history: 'none' });
}

function onKeydown(event) {
    if (event.key === 'Escape' && sheetOpen.value) sheetOpen.value = false;
}

watch(sheetOpen, async (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
    if (open) {
        lastChange = null;
        await nextTick();
        sheetCloseButton.value?.focus();
    }
});

onMounted(() => {
    window.addEventListener('popstate', onPopState);
    window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('popstate', onPopState);
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <div class="mb-12 flex flex-wrap items-baseline gap-x-5 gap-y-2 max-sm:mb-4 max-sm:gap-x-3">
        <h1 class="font-serif text-[34px] font-medium tracking-[0.12em] max-sm:text-[26px]">咖啡豆</h1>
        <span class="text-sm text-muted max-sm:text-[13px]" aria-live="polite">共 {{ total }} 支</span>
    </div>

    <div class="flex flex-wrap items-start gap-x-14 gap-y-12">
        <aside aria-label="篩選條件" class="flex min-w-0 flex-[1_1_220px] flex-col gap-8 max-sm:hidden">
            <fieldset v-for="group in facets" :key="group.key" class="border-b border-line pb-7">
                <legend class="mb-3 font-serif text-base tracking-[0.12em]">{{ group.label }}</legend>
                <div class="flex flex-col">
                    <label
                        v-for="option in group.options"
                        :key="option.value"
                        class="flex min-h-11 cursor-pointer items-center gap-3 text-sm"
                    >
                        <input
                            type="checkbox"
                            class="size-4 accent-ink"
                            :checked="filters[group.key].includes(option.value)"
                            @change="toggle(group.key, option.value)"
                        >
                        <span class="flex-1">{{ option.value }}</span>
                        <span class="font-latin text-[15px] text-muted">{{ option.count }}</span>
                    </label>
                </div>
            </fieldset>
        </aside>

        <div ref="resultsTop" class="min-w-0 flex-[999_1_560px] scroll-mt-6">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-3 border-b border-line pb-5 max-sm:grid max-sm:grid-cols-2 max-sm:gap-2 max-sm:border-0 max-sm:pb-0">
                <form role="search" class="flex min-h-12 flex-[1_1_260px] items-center gap-2.5 border border-outline bg-raised px-4 max-sm:col-span-2" @submit.prevent="search">
                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" class="shrink-0 text-muted"><circle cx="11" cy="11" r="6.5" /><path d="M16 16l4.5 4.5" /></svg>
                    <label for="product-search" class="sr-only">搜尋咖啡豆</label>
                    <input
                        id="product-search"
                        v-model="keyword"
                        type="search"
                        placeholder="搜尋名稱或風味，例如：莓果"
                        class="min-w-0 flex-1 bg-transparent text-sm outline-offset-4 placeholder:text-muted"
                    >
                </form>

                <button
                    type="button"
                    class="flex min-h-12 items-center justify-center gap-2 border border-ink text-sm sm:hidden"
                    aria-haspopup="dialog"
                    @click="sheetOpen = true"
                >
                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M4 7h16M7 12h10M10 17h4" /></svg>
                    篩選<template v-if="activeFilterCount">（{{ activeFilterCount }}）</template>
                </button>

                <label class="flex items-center gap-2.5 text-sm text-muted max-sm:block">
                    <span class="max-sm:sr-only">排序</span>
                    <!-- 自繪箭頭：箭頭到右框的距離與文字到左框相同（皆 12px）。 -->
                    <span class="relative inline-flex max-sm:flex">
                        <select
                            :value="filters.sort"
                            class="min-h-12 appearance-none border border-outline bg-raised pr-9 pl-3 text-sm text-ink max-sm:w-full max-sm:bg-transparent"
                            @change="changeSort"
                        >
                            <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <svg aria-hidden="true" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-ink"><path d="M5 9l7 7 7-7" /></svg>
                    </span>
                </label>
            </div>

            <div v-if="chips.length" class="flex flex-wrap items-center gap-2 pt-4 max-sm:flex-nowrap max-sm:overflow-x-auto max-sm:pt-3.5">
                <button
                    v-for="chip in chips"
                    :key="chip.id"
                    type="button"
                    :aria-label="chip.aria"
                    class="inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-2 rounded-full bg-ink pr-3 pl-4 text-[13px] text-paper"
                    @click="chip.remove"
                >
                    {{ chip.label }}
                    <svg aria-hidden="true" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
                <button type="button" class="min-h-11 shrink-0 cursor-pointer px-2 text-[13px] underline" @click="clearAll">清除全部</button>
            </div>

            <p v-if="failed" role="alert" class="pt-9 text-sm text-accent">
                載入咖啡豆時發生問題，請稍後再試一次。
            </p>

            <div v-else-if="total > 0 && results.products.length === 0" class="flex flex-col items-center gap-4 px-6 py-24 text-center max-sm:px-0 max-sm:py-16">
                <h2 class="font-serif text-[22px] font-medium tracking-[0.12em] max-sm:text-[19px]">這一頁沒有咖啡豆</h2>
                <p class="text-sm text-subtle">符合條件的咖啡豆共 {{ total }} 支，只有 {{ lastPage }} 頁。</p>
                <button type="button" class="mt-3 min-h-13 cursor-pointer bg-ink px-9 text-sm tracking-[0.14em] text-paper" @click="goToPage(1)">回到第一頁</button>
            </div>

            <div v-else-if="total === 0" class="flex flex-col items-center gap-4 px-6 py-24 text-center max-sm:px-0 max-sm:py-16">
                <h2 class="font-serif text-[22px] font-medium tracking-[0.12em] max-sm:text-[19px]">沒有符合條件的咖啡豆</h2>
                <p class="text-sm text-subtle">試著減少篩選條件，或換個關鍵字。</p>
                <button type="button" class="mt-3 min-h-13 cursor-pointer bg-ink px-9 text-sm tracking-[0.14em] text-paper" @click="clearAll">清除所有篩選</button>
            </div>

            <ul
                v-else
                class="grid grid-cols-[repeat(auto-fill,minmax(220px,1fr))] gap-x-7 gap-y-12 pt-9 transition-opacity max-sm:grid-cols-2 max-sm:gap-x-3.5 max-sm:gap-y-8 max-sm:pt-6"
                :class="{ 'opacity-50': loading }"
                :aria-busy="loading"
            >
                <li v-for="product in results.products" :key="product.id">
                    <ProductCard :product="product" />
                </li>
            </ul>

            <nav v-if="lastPage > 1 && !failed" aria-label="分頁" class="mt-18 flex items-center justify-center gap-1 font-latin text-[17px] max-sm:mt-10">
                <button
                    v-if="currentPage > 1"
                    type="button"
                    class="flex h-11 cursor-pointer items-center px-3 font-sans text-sm"
                    @click="goToPage(currentPage - 1)"
                >上一頁</button>
                <template v-for="(item, index) in pageItems" :key="`${item}-${index}`">
                    <span v-if="item === '…'" aria-hidden="true" class="flex size-11 items-center justify-center text-muted">…</span>
                    <button
                        v-else
                        type="button"
                        class="flex size-11 cursor-pointer items-center justify-center"
                        :class="item === currentPage ? 'bg-ink text-paper' : 'text-ink'"
                        :aria-current="item === currentPage ? 'page' : undefined"
                        :aria-label="`第 ${item} 頁`"
                        @click="item !== currentPage && goToPage(item)"
                    >{{ item }}</button>
                </template>
                <button
                    v-if="currentPage < lastPage"
                    type="button"
                    class="flex h-11 cursor-pointer items-center px-3 font-sans text-sm"
                    @click="goToPage(currentPage + 1)"
                >下一頁</button>
            </nav>
        </div>
    </div>

    <Teleport to="body">
        <div v-if="sheetOpen" class="fixed inset-0 z-50 flex flex-col sm:hidden">
            <button type="button" aria-label="關閉篩選" class="flex-1 cursor-pointer bg-ink/45" @click="sheetOpen = false"></button>
            <div role="dialog" aria-modal="true" aria-labelledby="filter-sheet-title" class="flex max-h-[85vh] flex-col rounded-t-2xl bg-paper">
                <div class="flex items-center justify-between border-b border-line py-3 pr-3 pl-5">
                    <h2 id="filter-sheet-title" class="font-serif text-[19px] font-medium tracking-[0.12em]">篩選</h2>
                    <div class="flex items-center gap-1">
                        <button type="button" class="min-h-11 cursor-pointer px-2.5 text-[13px] underline" @click="clearAll">清除全部</button>
                        <button ref="sheetCloseButton" type="button" aria-label="關閉篩選" class="flex size-11 cursor-pointer items-center justify-center" @click="sheetOpen = false">
                            <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M6 6l12 12M18 6L6 18" /></svg>
                        </button>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto px-5 pt-2 pb-6">
                    <fieldset v-for="group in facets" :key="group.key" class="border-b border-line py-5">
                        <legend class="float-left mb-3 w-full text-sm tracking-[0.12em]">{{ group.label }}</legend>
                        <div class="clear-both flex flex-wrap gap-2">
                            <button
                                v-for="option in group.options"
                                :key="option.value"
                                type="button"
                                class="min-h-11 cursor-pointer rounded-full border px-4 text-[13px]"
                                :class="filters[group.key].includes(option.value) ? 'border-ink bg-ink text-paper' : 'border-outline text-ink'"
                                :aria-pressed="filters[group.key].includes(option.value)"
                                @click="toggle(group.key, option.value)"
                            >{{ option.value }}</button>
                        </div>
                    </fieldset>
                </div>
                <div class="border-t border-line bg-raised px-5 pt-3 pb-6">
                    <button type="button" class="min-h-13 w-full cursor-pointer bg-ink text-[15px] tracking-[0.12em] text-paper" @click="sheetOpen = false">
                        查看 {{ total }} 支咖啡豆
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
