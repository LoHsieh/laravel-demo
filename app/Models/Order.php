<?php

namespace App\Models;

use App\Ordering\InvalidOrderTransition;
use App\Ordering\OrderStatus;
use Carbon\CarbonInterface;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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

    /** 已出貨的訂單在出貨滿這麼多天後自動改為已完成。 */
    public const AUTO_COMPLETE_AFTER_DAYS = 7;

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
            'shipped_at' => 'datetime',
            'cart_restore_notes' => 'array',
        ];
    }

    /**
     * 訂單是否因付款逾期而取消（訂單項目已放回購物車）。顧客自行取消的訂單不是。
     */
    public function cancelledForPaymentExpiry(): bool
    {
        return $this->cart_restore_notes !== null;
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    /**
     * 新的訂單編號，例如 BR261113K7Q2XM：BR + 下單日期（店家時區）+ 6 碼大寫英數。
     * 長度與字元都符合綠界交易編號（MerchantTradeNo，20 碼內英數）的限制。
     */
    public static function newNumber(): string
    {
        return 'BR'.now(config('app.shop_timezone'))->format('ymd').Str::upper(Str::random(6));
    }

    /**
     * 改變訂單狀態。不合法的轉換一律拒絕。
     *
     * 以「狀態仍是讀取時的狀態」為條件更新，兩個請求同時改同一筆訂單時（例如顧客取消與付款通知），
     * 只有先寫入的成功，後到的會因狀態已改變而被拒絕。改為已出貨時一併記下出貨時間。
     *
     * @throws InvalidOrderTransition
     */
    public function transitionTo(OrderStatus $to): void
    {
        $from = $this->status;

        if (! $from->canTransitionTo($to)) {
            throw InvalidOrderTransition::for($this, $from, $to);
        }

        $now = $this->freshTimestamp();
        $changes = ['status' => $to, ...($to === OrderStatus::Shipped ? ['shipped_at' => $now] : [])];

        $updated = static::whereKey($this->getKey())
            ->where('status', $from)
            ->update([...$changes, 'updated_at' => $now]);

        if ($updated === 0) {
            throw InvalidOrderTransition::for($this, $from, $to);
        }

        $this->forceFill($changes)->syncOriginalAttributes(array_keys($changes));
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 訂單是否接受在 $at（預設為現在）完成的付款：待付款，且 $at 在付款期限之前。
     * 顧客能否（重新）付款，以及付款成功能否讓訂單變成已付款，都以此判斷。
     */
    public function acceptsPayment(?CarbonInterface $at = null): bool
    {
        return $this->status === OrderStatus::PendingPayment && ($at ?? now())->lt($this->payment_due_at);
    }

    /**
     * 已超過付款期限、但仍是待付款（排程尚未處理）的訂單。
     *
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->where('status', OrderStatus::PendingPayment)->where('payment_due_at', '<=', now());
    }

    /**
     * 計入營收的訂單（已付款、已出貨、已完成）。
     *
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function countedAsRevenue(Builder $query): void
    {
        $query->whereIn('status', array_filter(OrderStatus::cases(), fn (OrderStatus $status) => $status->countsAsRevenue()));
    }

    /**
     * 出貨已滿 7 天、但仍是已出貨（排程尚未處理）的訂單。
     *
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function dueForCompletion(Builder $query): void
    {
        $query->where('status', OrderStatus::Shipped)
            ->where('shipped_at', '<=', now()->subDays(self::AUTO_COMPLETE_AFTER_DAYS));
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('id');
    }
}
