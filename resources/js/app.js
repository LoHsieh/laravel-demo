import { createApp } from 'vue';
import VariantPrices from './components/VariantPrices.vue';

const components = { VariantPrices };

document.querySelectorAll('[data-vue-component]').forEach((element) => {
    const { vueComponent, props } = element.dataset;

    createApp(components[vueComponent], JSON.parse(props)).mount(element);
});
