<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    variants: { type: Array, required: true },
});

const selectedId = ref(props.variants[0]?.id);
const selected = computed(() => props.variants.find((variant) => variant.id === selectedId.value));
const formatPrice = (price) => `NT$${price.toLocaleString('en-US')}`;
</script>

<template>
    <div v-if="selected" class="mt-2 flex flex-col gap-2">
        <div class="flex flex-wrap gap-2" role="group" aria-label="規格">
            <button
                v-for="variant in variants"
                :key="variant.id"
                type="button"
                class="min-h-11 min-w-11 cursor-pointer border px-3 font-latin text-[15px]"
                :class="variant.id === selectedId ? 'border-ink bg-ink text-paper' : 'border-outline'"
                :aria-pressed="variant.id === selectedId"
                @click="selectedId = variant.id"
            >
                {{ variant.weight_grams }}g
            </button>
        </div>
        <p class="font-latin text-[17px]">{{ formatPrice(selected.price) }}</p>
    </div>
</template>
