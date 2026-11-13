<?php

namespace App\Payment;

/**
 * 導向金流服務付款頁的表單：瀏覽器以 POST 把 fields 送到 action。
 */
final readonly class CheckoutForm
{
    /**
     * @param  array<string, string>  $fields
     */
    public function __construct(
        public string $action,
        public array $fields,
    ) {}
}
