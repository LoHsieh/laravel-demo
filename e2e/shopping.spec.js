// 主要購物流程的端對端測試：瀏覽 → 選規格與研磨方式 → 加入購物車 → 登入 → 結帳 → 模擬付款成功通知 → 訂單變成已付款。
// 綠界在邊界替換：攔下前往綠界付款頁的表單，改由測試送出帶正確檢查碼的付款通知，不連到綠界。
import { expect, test } from '@playwright/test';
import { ECPAY_CHECKOUT_URL, MERCHANT_ID, checkMacValue, paymentSucceededNotification } from './ecpay.js';

test('a customer buys coffee beans and the order becomes paid after the 綠界 payment notification', async ({ page, request }) => {
    // 綠界付款頁的替身：記下網站送來的表單，顯示一個空白頁
    let checkout = null;
    await page.route(ECPAY_CHECKOUT_URL, async (route) => {
        checkout = Object.fromEntries(new URLSearchParams(route.request().postData() ?? ''));
        await route.fulfill({ contentType: 'text/html; charset=utf-8', body: '<h1>綠界付款頁（測試替身）</h1>' });
    });

    // 瀏覽
    await page.goto('/beans');
    await page.getByRole('link', { name: /巴西 喜拉朵 日曬/ }).click();
    await expect(page.getByRole('heading', { level: 1, name: '巴西 喜拉朵 日曬' })).toBeVisible();

    // 選規格與研磨方式，加入購物車
    await page.getByRole('button', { name: /^250g/ }).click();
    await page.getByRole('button', { name: /^手沖/ }).click();
    await page.getByRole('button', { name: '加入購物車' }).click();
    await expect(page.getByText('已加入購物車：250g・手沖 × 1')).toBeVisible();

    // 購物車 → 結帳時要求登入
    await page.getByRole('link', { name: '前往購物車' }).click();
    await page.getByRole('link', { name: '前往結帳' }).click();
    await expect(page).toHaveURL(/\/login$/);

    await page.getByLabel('Email').fill('e2e@example.com');
    await page.getByLabel('密碼').fill('password');
    await page.getByRole('button', { name: '登入' }).click();

    // 登入後回到結帳，訪客購物車已併入該顧客的購物車
    await expect(page).toHaveURL(/\/checkout$/);
    await expect(page.getByText('250g・手沖')).toBeVisible();
    await page.getByLabel('收件人姓名').fill('王小明');
    await page.getByLabel('收件人電話').fill('0912345678');
    await page.getByLabel('收件地址').fill('台北市大安區復興南路一段 1 號');
    await page.getByRole('button', { name: '確認下單' }).click();

    // 下單後自動前往綠界付款頁
    await expect(page).toHaveURL(ECPAY_CHECKOUT_URL);
    expect(checkout).toMatchObject({ MerchantID: MERCHANT_ID, ChoosePayment: 'Credit', TotalAmount: '600' });
    expect(checkout.CheckMacValue).toBe(checkMacValue(checkout));

    // 綠界伺服器送來付款成功通知
    const notification = paymentSucceededNotification(checkout);
    const notified = await request.post(checkout.ReturnURL, { form: notification });
    expect(await notified.text()).toBe('1|OK');

    // 綠界把顧客的瀏覽器導回網站（跨站 POST）
    await page.evaluate(({ action, fields }) => {
        const form = document.createElement('form');
        form.method = 'post';
        form.action = action;
        for (const [name, value] of Object.entries(fields)) {
            form.append(Object.assign(document.createElement('input'), { type: 'hidden', name, value }));
        }
        document.body.append(form);
        form.submit();
    }, { action: checkout.OrderResultURL, fields: notification });

    await expect(page.getByRole('heading', { level: 1, name: '付款成功' })).toBeVisible();
    const orderNumber = (await page.locator('dt:text-is("訂單編號") + dd').textContent()).trim();

    // 顧客在會員中心看到訂單變成已付款
    await page.goto('/account');
    await expect(page.getByRole('link', { name: orderNumber })).toContainText('已付款');
});
