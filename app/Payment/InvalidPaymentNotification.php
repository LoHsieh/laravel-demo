<?php

namespace App\Payment;

use RuntimeException;

/**
 * 付款通知的檢查碼錯誤或內容不完整，可能是偽造的，不可採信。
 */
final class InvalidPaymentNotification extends RuntimeException {}
