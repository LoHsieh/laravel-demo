import { createApp } from 'vue';
import ProductBrowser from './components/ProductBrowser.vue';

const components = { ProductBrowser };

document.querySelectorAll('[data-vue-component]').forEach((element) => {
    const { vueComponent, props } = element.dataset;

    createApp(components[vueComponent], JSON.parse(props)).mount(element);
});

// 首頁「本週烘焙」橫向卡片列的左右箭頭，每次捲動一張卡片。
document.querySelectorAll('[data-rail-scroll]').forEach((button) => {
    const rail = document.getElementById(button.getAttribute('aria-controls'));

    button.addEventListener('click', () => {
        const card = rail?.firstElementChild;
        if (!card) return;

        const gap = parseFloat(getComputedStyle(rail).columnGap) || 0;
        rail.scrollBy({ left: Number(button.dataset.railScroll) * (card.offsetWidth + gap), behavior: 'smooth' });
    });
});
