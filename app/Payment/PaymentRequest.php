<?php

namespace App\Payment;

use Carbon\CarbonImmutable;

/**
 * 一次付款嘗試要向金流服務請款的內容。
 */
final readonly class PaymentRequest
{
    /**
     * @param  string  $merchantTradeNo  這次付款嘗試的交易編號，每次不同
     * @param  list<string>  $itemNames  顯示在付款頁上的項目名稱
     * @param  CarbonImmutable  $dueAt  訂單的付款期限；金流服務支援時，付款頁的期限不得超過它
     */
    public function __construct(
        public string $merchantTradeNo,
        public int $amount,
        public array $itemNames,
        public CarbonImmutable $dueAt,
    ) {}
}
