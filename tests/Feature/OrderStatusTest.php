<?php

use App\Models\Order;
use App\Ordering\InvalidOrderTransition;
use App\Ordering\OrderStatus;

/**
 * 規格書（docs/specs/bairi-v1.md）「訂單狀態」中允許的轉換，其餘一律拒絕。
 * 這裡直接從 Order::transitionTo() 驗證全部組合；顧客取消的 HTTP 行為見 OrderCenterTest。
 */
const LEGAL_ORDER_TRANSITIONS = [
    ['pending_payment', 'paid'],       // 付款成功
    ['pending_payment', 'cancelled'],  // 付款逾期、顧客取消
    ['paid', 'shipped'],               // 店家出貨
    ['shipped', 'completed'],          // 店家完成、出貨滿 7 天
    ['cancelled', 'payment_exception'], // 取消後才收到付款成功
];

dataset('every pair of 訂單狀態', function () {
    foreach (OrderStatus::cases() as $from) {
        foreach (OrderStatus::cases() as $to) {
            yield "{$from->value} → {$to->value}" => [$from, $to];
        }
    }
});

it('allows only the transitions in the 訂單狀態 rules', function (OrderStatus $from, OrderStatus $to) {
    $order = Order::factory()->create(['status' => $from]);
    $legal = in_array([$from->value, $to->value], LEGAL_ORDER_TRANSITIONS, true);

    if (! $legal) {
        expect(fn () => $order->transitionTo($to))->toThrow(InvalidOrderTransition::class);
    } else {
        $order->transitionTo($to);
    }

    expect($order->status)->toBe($legal ? $to : $from)
        ->and($order->fresh()->status)->toBe($legal ? $to : $from);
})->with('every pair of 訂單狀態');

it('rejects a transition when the 訂單 changed status after it was loaded', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PendingPayment]);
    $stale = Order::find($order->id);

    $order->transitionTo(OrderStatus::Cancelled);

    expect(fn () => $stale->transitionTo(OrderStatus::Paid))->toThrow(InvalidOrderTransition::class)
        ->and($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});
