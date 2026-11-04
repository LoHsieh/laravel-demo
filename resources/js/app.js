import { createApp } from 'vue';
import ProductBrowser from './components/ProductBrowser.vue';
import ProductPurchase from './components/ProductPurchase.vue';

const components = { ProductBrowser, ProductPurchase };

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

// 詳情頁「你可能也喜歡」超過四張時的展開／收合。
document.querySelectorAll('[data-related-toggle]').forEach((button) => {
    const list = document.getElementById(button.getAttribute('aria-controls'));
    const label = button.querySelector('[data-related-toggle-label]');

    button.addEventListener('click', () => {
        const expanded = button.getAttribute('aria-expanded') !== 'true';

        button.setAttribute('aria-expanded', String(expanded));
        label.textContent = expanded ? button.dataset.labelExpanded : button.dataset.labelCollapsed;
        list.querySelectorAll('[data-related-extra]').forEach((item) => {
            item.hidden = !expanded;
        });
    });
});
