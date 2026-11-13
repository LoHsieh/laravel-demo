import { createApp } from 'vue';
import CartPage from './components/CartPage.vue';
import ProductBrowser from './components/ProductBrowser.vue';
import ProductPurchase from './components/ProductPurchase.vue';

const components = { CartPage, ProductBrowser, ProductPurchase };

document.querySelectorAll('[data-vue-component]').forEach((element) => {
    const { vueComponent, props } = element.dataset;

    createApp(components[vueComponent], JSON.parse(props)).mount(element);
});

// 頁首的購物車數量，在加入、修改或移除購物車項目後更新（cart.js）。
window.addEventListener('cart:updated', ({ detail }) => {
    document.querySelectorAll('[data-cart-count]').forEach((element) => {
        element.textContent = detail.count;
    });
    document.querySelectorAll('[data-cart-link]').forEach((link) => {
        link.setAttribute('aria-label', `購物車，${detail.count} 件商品`);
    });
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

// 付款期限倒數（components/payment-deadline.blade.php）。以伺服器給的剩餘秒數倒數，
// 到期後顯示提示，再倒數幾秒跳回購物車。
const REDIRECT_AFTER_EXPIRY_SECONDS = 5;

document.querySelectorAll('[data-payment-countdown]').forEach((element) => {
    const deadline = performance.now() + Number(element.dataset.secondsLeft) * 1000;
    const clock = element.querySelector('[data-countdown-clock]');
    const remaining = element.querySelector('[data-countdown-remaining]');
    const expired = element.querySelector('[data-payment-expired]');
    const redirectSeconds = element.querySelector('[data-redirect-seconds]');

    const tick = () => {
        const secondsLeft = Math.max(0, Math.ceil((deadline - performance.now()) / 1000));
        clock.textContent = `${String(Math.floor(secondsLeft / 60)).padStart(2, '0')}:${String(secondsLeft % 60).padStart(2, '0')}`;

        if (secondsLeft > 0) {
            setTimeout(tick, 1000);
            return;
        }

        remaining.hidden = true;
        expired.hidden = false;
        let countdown = REDIRECT_AFTER_EXPIRY_SECONDS;
        const redirectTick = () => {
            redirectSeconds.textContent = countdown;
            if (countdown === 0) {
                window.location.assign(element.dataset.expiredRedirect);
                return;
            }
            countdown -= 1;
            setTimeout(redirectTick, 1000);
        };
        redirectTick();
    };

    tick();
});
