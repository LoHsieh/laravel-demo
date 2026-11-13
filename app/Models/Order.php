<?php

namespace App\Models;

use App\Ordering\InvalidOrderTransition;
use App\Ordering\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * 顧客結帳後成立的訂單。網址與畫面上都以訂單編號（number）識別，不使用資料庫 id（ADR 0004）。
 */
#[Fillable([
    'number', 'status', 'recipient_name', 'recipient_phone', 'recipient_address',
    'subtotal', 'shipping_fee', 'total', 'payment_due_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'shipping_fee' => 'integer',
            'total' => 'integer',
            'payment_due_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    /**
     * 新的訂單編號，例如 BR261113K7Q2XM：BR + 下單日期（台北時間）+ 6 碼大寫英數。
     * 長度與字元都符合綠界交易編號（MerchantTradeNo，20 碼內英數）的限制。
     */
    public static function newNumber(): string
    {
        return 'BR'.now('Asia/Taipei')->format('ymd').Str::upper(Str::random(6));
    }

    /**
     * 改變訂單狀態。不合法的轉換一律拒絕。
     *
     * 以「狀態仍是讀取時的狀態」為條件更新，兩個請求同時改同一筆訂單時（例如顧客取消與付款通知），
     * 只有先寫入的成功，後到的會因狀態已改變而被拒絕。
     *
     * @throws InvalidOrderTransition
     */
    public function transitionTo(OrderStatus $to): void
    {
        $from = $this->status;

        if (! $from->canTransitionTo($to)) {
            throw InvalidOrderTransition::for($this, $from, $to);
        }

        $updated = static::whereKey($this->getKey())
            ->where('status', $from)
            ->update(['status' => $to, 'updated_at' => $this->freshTimestamp()]);

        if ($updated === 0) {
            throw InvalidOrderTransition::for($this, $from, $to);
        }

        $this->forceFill(['status' => $to])->syncOriginalAttribute('status');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }
}
