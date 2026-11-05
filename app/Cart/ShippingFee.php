<?php

namespace App\Cart;

/**
 * 運費規則唯一的出處：宅配 NT$100，商品小計達 NT$1,000（含）免運。
 * 購物車、結帳與訂單都向這裡取運費，不各自計算。
 */
final class ShippingFee
{
    public const AMOUNT = 100;

    public const FREE_THRESHOLD = 1000;

    public static function for(int $subtotal): int
    {
        return $subtotal >= self::FREE_THRESHOLD ? 0 : self::AMOUNT;
    }

    /**
     * 距離免運門檻還差多少商品金額，已達門檻時為零。
     */
    public static function remainingForFree(int $subtotal): int
    {
        return max(0, self::FREE_THRESHOLD - $subtotal);
    }
}
