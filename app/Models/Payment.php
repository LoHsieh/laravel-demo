<?php

namespace App\Models;

use App\Ordering\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * 付款：訂單的一次付款嘗試。付款失敗後重新付款會建立新的一筆。
 */
#[Fillable([
    'merchant_trade_no', 'gateway_trade_no', 'status', 'amount', 'result_message', 'simulated', 'paid_at',
])]
class Payment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'simulated' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * 新的交易編號：訂單編號（14 碼）+ 6 碼大寫英數，共 20 碼，符合綠界 MerchantTradeNo 的限制。
     */
    public static function newMerchantTradeNo(Order $order): string
    {
        return $order->number.Str::upper(Str::random(6));
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
