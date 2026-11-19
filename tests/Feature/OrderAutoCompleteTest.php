<?php

use App\Models\Admin;
use App\Models\Order;
use App\Ordering\OrderStatus;

beforeEach(function () {
    $this->travelTo(now()->setDateTime(2026, 11, 13, 6, 0)); // 台北時間 14:00
    $this->actingAs(Admin::factory()->create(), 'admin');
});

function shipOrder(Order $order): void
{
    test()->patch("/admin/orders/{$order->number}/status", ['status' => 'shipped'])->assertSessionHasNoErrors();
}

it('completes a 已出貨 訂單 on the scheduler once it has been shipped for 7 days', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Paid]);
    shipOrder($order);

    $this->travel(7)->days();
    $this->travel(-1)->hours();
    $this->artisan('schedule:run')->assertSuccessful();
    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);

    $this->travel(1)->hours();
    $this->artisan('schedule:run')->assertSuccessful();
    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
});

it('leaves 訂單 that are not 已出貨 alone', function () {
    $paid = Order::factory()->create(['status' => OrderStatus::Paid]);
    $cancelled = Order::factory()->create(['status' => OrderStatus::Cancelled]);

    $this->travel(8)->days();
    $this->artisan('schedule:run')->assertSuccessful();

    expect($paid->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($cancelled->fresh()->status)->toBe(OrderStatus::Cancelled);
});
