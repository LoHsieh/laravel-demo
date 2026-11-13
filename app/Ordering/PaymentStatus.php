<?php

namespace App\Ordering;

/**
 * 一次付款嘗試的結果，以金流服務的付款通知為準。
 */
enum PaymentStatus: string
{
    /** 已導向金流服務，尚未收到付款通知 */
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
