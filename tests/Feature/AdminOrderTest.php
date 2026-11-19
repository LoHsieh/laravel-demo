<?php

use App\Catalog\Grind;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Variant;
use App\Ordering\OrderStatus;

beforeEach(function () {
    $this->actingAs(Admin::factory()->create(), 'admin');
});

it('lists every 訂單 and filters them by 狀態', function () {
    $paid = Order::factory()->create(['status' => OrderStatus::Paid]);
    $shipped = Order::factory()->create(['status' => OrderStatus::Shipped]);
    $cancelled = Order::factory()->create(['status' => OrderStatus::Cancelled]);

    $this->get('/admin/orders')
        ->assertOk()
        ->assertSeeText([$paid->number, $shipped->number, $cancelled->number]);

    $this->get('/admin/orders?status=paid')
        ->assertOk()
        ->assertSeeText($paid->number)
        ->assertDontSeeText($shipped->number)
        ->assertDontSeeText($cancelled->number);
});

it('shows the 訂單項目 with their 研磨方式 and where to ship them on the 訂單 detail', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Paid,
        'recipient_name' => '林小美',
        'recipient_phone' => '0912-345-678',
        'recipient_address' => '台北市大安區復興南路一段 100 號',
    ]);
    $variant = Variant::factory()->for(Product::factory()->create(['name' => '耶加雪菲 孔加 水洗']))
        ->create(['weight_grams' => 250]);
    $order->items()->create([...OrderItem::snapshotOf($variant), 'grind' => Grind::Espresso, 'unit_price' => 420, 'quantity' => 2]);

    $this->get('/admin/orders')->assertSee(route('admin.orders.show', $order), false);

    $this->get("/admin/orders/{$order->number}")
        ->assertOk()
        ->assertSeeText(['耶加雪菲 孔加 水洗', '250g', '義式', '× 2'])
        ->assertSeeText(['林小美', '0912-345-678', '台北市大安區復興南路一段 100 號']);
});

it('lets a 店家 ship a 已付款 訂單 and then complete it', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Paid]);

    $this->get("/admin/orders/{$order->number}")->assertSeeText('改為已出貨')->assertDontSeeText('改為已完成');

    $this->patch("/admin/orders/{$order->number}/status", ['status' => 'shipped'])
        ->assertRedirect("/admin/orders/{$order->number}");
    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);

    $this->get("/admin/orders/{$order->number}")->assertSeeText('改為已完成')->assertDontSeeText('改為已出貨');

    $this->patch("/admin/orders/{$order->number}/status", ['status' => 'completed'])
        ->assertRedirect("/admin/orders/{$order->number}");
    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
});

it('rejects moving a 訂單 to a 狀態 that is not its legal next one', function (OrderStatus $from, string $to) {
    $order = Order::factory()->create(['status' => $from]);

    $this->from("/admin/orders/{$order->number}")
        ->patch("/admin/orders/{$order->number}/status", ['status' => $to])
        ->assertRedirect("/admin/orders/{$order->number}")
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe($from);
})->with([
    '已取消 → 已出貨' => [OrderStatus::Cancelled, 'shipped'],
    '待付款 → 已出貨' => [OrderStatus::PendingPayment, 'shipped'],
    '已付款 → 已完成' => [OrderStatus::Paid, 'completed'],
    '已完成 → 已出貨' => [OrderStatus::Completed, 'shipped'],
    // 付款成功只由付款通知觸發，店家不能手動改成已付款
    '待付款 → 已付款' => [OrderStatus::PendingPayment, 'paid'],
]);

it('calls out 付款異常 訂單 on the list so the 店家 can contact the 顧客', function () {
    $exception = Order::factory()->create(['status' => OrderStatus::PaymentException]);
    Order::factory()->create(['status' => OrderStatus::Paid]);

    $this->get('/admin/orders?status=paid')
        ->assertOk()
        ->assertSeeText('有 1 筆付款異常的訂單需要聯絡顧客處理')
        ->assertDontSeeText($exception->number);

    $this->get('/admin/orders?status=payment_exception')
        ->assertOk()
        ->assertSeeText($exception->number);
});
