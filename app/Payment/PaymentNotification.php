<?php

namespace App\Payment;

use Carbon\CarbonImmutable;

/**
 * 金流服務送來、已驗證過檢查碼的付款結果。
 */
final readonly class PaymentNotification
{
    /**
     * @param  string  $merchantTradeNo  付款嘗試的交易編號（PaymentRequest::$merchantTradeNo）
     * @param  string  $gatewayTradeNo  金流服務的交易編號，用來辨識重複送達的通知
     * @param  bool  $simulated  金流服務後台的模擬付款，沒有實際扣款
     */
    public function __construct(
        public string $merchantTradeNo,
        public string $gatewayTradeNo,
        public bool $succeeded,
        public int $amount,
        public string $message,
        public ?CarbonImmutable $paidAt,
        public bool $simulated,
    ) {}
}
