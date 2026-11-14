<?php

namespace App\Payment\Ecpay;

use App\Payment\CheckoutForm;
use App\Payment\InvalidPaymentNotification;
use App\Payment\PaymentGateway;
use App\Payment\PaymentNotification;
use App\Payment\PaymentRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * 綠界全方位金流（AioCheckOut V5）的信用卡一次付清。
 *
 * 綠界的信用卡付款沒有可設定付款期限的參數（ADR 0005），PaymentRequest::$dueAt 無法送出；
 * 改由訂單端只在付款期限內才開始付款，並在收到付款通知時再檢查一次付款期限。
 *
 * @see https://developers.ecpay.com.tw/?p=2862 產生訂單
 * @see https://developers.ecpay.com.tw/?p=2878 付款結果通知
 */
final class EcpayGateway implements PaymentGateway
{
    /** 綠界 ItemName 上限 400 字元，留出 URL encode 前後的餘裕 */
    private const ITEM_NAME_LIMIT = 200;

    public function __construct(
        private readonly string $merchantId,
        private readonly CheckMacValue $checkMac,
        private readonly string $checkoutUrl,
        private readonly string $notifyUrl,
        private readonly string $resultUrl,
    ) {}

    public function checkoutForm(PaymentRequest $request): CheckoutForm
    {
        $fields = [
            'MerchantID' => $this->merchantId,
            'MerchantTradeNo' => $request->merchantTradeNo,
            'MerchantTradeDate' => now('Asia/Taipei')->format('Y/m/d H:i:s'),
            'PaymentType' => 'aio',
            'TotalAmount' => (string) $request->amount,
            'TradeDesc' => '焙日咖啡豆訂單',
            'ItemName' => $this->itemName($request->itemNames),
            'ReturnURL' => $this->notifyUrl,
            'OrderResultURL' => $this->resultUrl,
            'ChoosePayment' => 'Credit',
            'EncryptType' => '1',
        ];

        return new CheckoutForm($this->checkoutUrl, [...$fields, 'CheckMacValue' => $this->checkMac->for($fields)]);
    }

    public function parseNotification(array $payload): PaymentNotification
    {
        if (! $this->checkMac->verify($payload)) {
            throw new InvalidPaymentNotification('綠界付款通知的檢查碼錯誤。');
        }

        foreach (['MerchantID', 'MerchantTradeNo', 'TradeNo', 'RtnCode', 'TradeAmt'] as $field) {
            if (! is_string($payload[$field] ?? null) || $payload[$field] === '') {
                throw new InvalidPaymentNotification("綠界付款通知缺少 {$field}。");
            }
        }

        if ($payload['MerchantID'] !== $this->merchantId) {
            throw new InvalidPaymentNotification('綠界付款通知的特店編號不符。');
        }

        $succeeded = $payload['RtnCode'] === '1';
        $paymentDate = $payload['PaymentDate'] ?? '';

        return new PaymentNotification(
            merchantTradeNo: $payload['MerchantTradeNo'],
            gatewayTradeNo: $payload['TradeNo'],
            succeeded: $succeeded,
            amount: (int) $payload['TradeAmt'],
            message: (string) ($payload['RtnMsg'] ?? ''),
            paidAt: $succeeded && is_string($paymentDate) && $paymentDate !== ''
                ? CarbonImmutable::createFromFormat('Y/m/d H:i:s', $paymentDate, 'Asia/Taipei')->utc()
                : null,
            simulated: ($payload['SimulatePaid'] ?? '0') === '1',
        );
    }

    /**
     * 綠界以 # 分隔多個項目；不支援特殊符號，只保留常見文字。
     *
     * @param  list<string>  $names
     */
    private function itemName(array $names): string
    {
        $cleaned = array_map(fn (string $name) => trim(preg_replace('/[^\p{L}\p{N} ._-]+/u', ' ', $name)), $names);

        return Str::limit(implode('#', $cleaned), self::ITEM_NAME_LIMIT, '');
    }
}
