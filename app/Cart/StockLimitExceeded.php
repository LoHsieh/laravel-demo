<?php

namespace App\Cart;

use App\Models\Variant;
use Illuminate\Validation\ValidationException;

/**
 * 購物車項目的數量超過規格目前的庫存。以表單驗證錯誤（422）回應，訊息直接顯示給訪客。
 */
final class StockLimitExceeded
{
    /**
     * @param  int  $inCart  購物車中這個規格（所有研磨方式）已有的數量
     */
    public static function whenAdding(Variant $variant, int $inCart): ValidationException
    {
        return self::exception($variant, $inCart > 0 ? "購物車中已有 {$inCart} 包。" : '');
    }

    /**
     * @param  int  $inOtherItems  購物車中這個規格其他研磨方式的數量
     */
    public static function whenChanging(Variant $variant, int $inOtherItems): ValidationException
    {
        return self::exception($variant, $inOtherItems > 0 ? "購物車中其他研磨方式已有 {$inOtherItems} 包。" : '');
    }

    private static function exception(Variant $variant, string $detail): ValidationException
    {
        $message = $variant->stock === 0
            ? '此規格已售完。'
            : "此規格目前僅剩 {$variant->stock} 包".($detail === '' ? '。' : "，{$detail}");

        return ValidationException::withMessages(['quantity' => $message]);
    }
}
