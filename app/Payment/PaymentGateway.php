<?php

namespace App\Payment;

/**
 * 金流服務。訂單邏輯只透過這個介面與 PaymentRequest、CheckoutForm、PaymentNotification 和金流服務往來，
 * 不依賴綠界的參數名稱、檢查碼或網址；測試或將來替換金流時只要換掉實作。
 */
interface PaymentGateway
{
    /**
     * 顧客要被導向的付款頁：一份由瀏覽器送出的表單。
     */
    public function checkoutForm(PaymentRequest $request): CheckoutForm;

    /**
     * 驗證並解讀金流服務送來的付款結果。
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws InvalidPaymentNotification 檢查碼錯誤或缺少必要欄位
     */
    public function parseNotification(array $payload): PaymentNotification;
}
