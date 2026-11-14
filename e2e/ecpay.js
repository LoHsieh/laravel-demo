// 端對端測試的綠界替身：組出與綠界相同格式、帶正確檢查碼的付款通知。
// 檢查碼演算法依 https://developers.ecpay.com.tw/?p=2902，刻意與 app/Payment/Ecpay/CheckMacValue.php 分開實作，
// 測試同時以它驗證網站送往綠界的表單，兩邊算法不一致時會失敗。
import { createHash } from 'node:crypto';

export const ECPAY_CHECKOUT_URL = 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5';

// 綠界公開的測試特店（https://developers.ecpay.com.tw/?p=2856），與 config/services.php 的預設值相同
export const MERCHANT_ID = '3002607';
const HASH_KEY = 'pwFHCqoQZGmho4w6';
const HASH_IV = 'EkRm7iFT261dpevs';

/**
 * 綠界採用 .NET 的 URL encode：英數與 -_.!*() 不編碼、空白變成 +，其餘以 %XX 編碼。
 */
const dotnetUrlEncode = (value) =>
    encodeURIComponent(value)
        .replace(/%20/g, '+')
        .replace(/~/g, '%7E')
        .replace(/'/g, '%27');

/**
 * @param {Record<string, string>} params 若含 CheckMacValue 會被忽略
 */
export function checkMacValue(params) {
    const query = Object.keys(params)
        .filter((key) => key !== 'CheckMacValue')
        .sort((a, b) => (a.toLowerCase() < b.toLowerCase() ? -1 : a.toLowerCase() > b.toLowerCase() ? 1 : 0))
        .map((key) => `${key}=${params[key]}`)
        .join('&');
    const encoded = dotnetUrlEncode(`HashKey=${HASH_KEY}&${query}&HashIV=${HASH_IV}`).toLowerCase();

    return createHash('sha256').update(encoded).digest('hex').toUpperCase();
}

/**
 * 綠界的時間格式（台灣時間 yyyy/MM/dd HH:mm:ss）。
 */
const ecpayTime = (date) =>
    new Intl.DateTimeFormat('sv-SE', {
        timeZone: 'Asia/Taipei',
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
        hourCycle: 'h23',
    }).format(date).replaceAll('-', '/');

/**
 * 依網站送往綠界的表單，組出信用卡付款成功的付款結果通知。
 *
 * @param {Record<string, string>} checkout 網站送往綠界的表單欄位
 */
export function paymentSucceededNotification(checkout) {
    const now = new Date();
    const payload = {
        MerchantID: MERCHANT_ID,
        MerchantTradeNo: checkout.MerchantTradeNo,
        StoreID: '',
        RtnCode: '1',
        RtnMsg: '交易成功',
        TradeNo: String(now.getTime()).slice(-16),
        TradeAmt: checkout.TotalAmount,
        PaymentDate: ecpayTime(now),
        PaymentType: 'Credit_CreditCard',
        PaymentTypeChargeFee: '0',
        TradeDate: checkout.MerchantTradeDate,
        SimulatePaid: '0',
        CustomField1: '', CustomField2: '', CustomField3: '', CustomField4: '',
    };

    return { ...payload, CheckMacValue: checkMacValue(payload) };
}
