<?php

use App\Catalog\Grind;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Variant;
use App\Ordering\OrderStatus;

beforeEach(function () {
    $this->customer = Customer::factory()->create();
    $this->actingAs($this->customer, 'customer');
});

it('lists the 顧客\'s 訂單 with their status in the 會員中心, newest first, and no one else\'s', function () {
    $older = Order::factory()->for($this->customer)->create([
        'status' => OrderStatus::Shipped, 'total' => 1640, 'created_at' => now()->subDays(3),
    ]);
    $newer = Order::factory()->for($this->customer)->create(['status' => OrderStatus::PendingPayment, 'total' => 940]);
    $someoneElses = Order::factory()->create();

    $this->get('/account')
        ->assertOk()
        ->assertSeeTextInOrder([$newer->number, '待付款', 'NT$940', $older->number, '已出貨', 'NT$1,640'])
        ->assertSee(route('account.orders.show', $newer), false)
        ->assertDontSeeText($someoneElses->number);
});

/**
 * 顧客的一筆訂單，含兩筆訂單項目：耶加雪菲 250g 義式 × 2（庫存剩 3）、慧蘭 100g 原豆 × 1（庫存剩 5）。
 */
function orderWithItems(Customer $customer, array $attributes = []): Order
{
    $order = Order::factory()->for($customer)->create([
        'subtotal' => 1220, 'shipping_fee' => 0, 'total' => 1220, ...$attributes,
    ]);

    $yirgacheffe = Variant::factory()->for(Product::factory()->create(['name' => '耶加雪菲 孔加 水洗']))
        ->create(['weight_grams' => 250, 'price' => 420, 'stock' => 3]);
    $huila = Variant::factory()->for(Product::factory()->create(['name' => '哥倫比亞 慧蘭 水洗']))
        ->create(['weight_grams' => 100, 'price' => 380, 'stock' => 5]);

    $order->items()->createMany([
        ['variant_id' => $yirgacheffe->id, 'grind' => Grind::Espresso, 'unit_price' => 420, 'quantity' => 2],
        ['variant_id' => $huila->id, 'grind' => Grind::WholeBean, 'unit_price' => 380, 'quantity' => 1],
    ]);

    return $order;
}

it('shows the 訂單項目, 研磨方式, amounts, recipient info, status and payment deadline of a 訂單', function () {
    $this->travelTo(now()->setDateTime(2026, 11, 13, 6, 0)); // 台北時間 14:00
    $order = orderWithItems($this->customer, [
        'recipient_name' => '王大明',
        'recipient_phone' => '02-2700-1234',
        'recipient_address' => '新北市板橋區文化路一段 1 號',
        'payment_due_at' => now()->addMinutes(30),
    ]);

    $this->get(route('account.orders.show', $order))
        ->assertOk()
        ->assertSeeText($order->number)
        ->assertSeeText('待付款')
        ->assertSeeText('2026/11/13 14:30')
        ->assertSeeTextInOrder(['耶加雪菲 孔加 水洗', '250g・義式', 'NT$420', '× 2', 'NT$840'])
        ->assertSeeTextInOrder(['哥倫比亞 慧蘭 水洗', '100g・原豆', 'NT$380', '× 1'])
        ->assertSeeTextInOrder(['商品小計', 'NT$1,220', '運費', '免運', '總計', 'NT$1,220'])
        ->assertSeeText('王大明')
        ->assertSeeText('02-2700-1234')
        ->assertSeeText('新北市板橋區文化路一段 1 號');
});

it('refuses to show another 顧客\'s 訂單', function () {
    $someoneElses = orderWithItems(Customer::factory()->create());

    $this->get(route('account.orders.show', $someoneElses))->assertNotFound();
});

/**
 * 訂單項目對應規格的目前庫存，依訂單項目順序。
 */
function stockOf(Order $order): array
{
    return $order->items()->with('variant')->get()->map(fn ($item) => $item->variant->stock)->all();
}

it('lets the 顧客 cancel a 待付款 訂單 and returns its 庫存保留', function () {
    $order = orderWithItems($this->customer);

    $this->post(route('account.orders.cancel', $order))
        ->assertRedirect(route('account.orders.show', $order));

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and(stockOf($order))->toBe([5, 6]);

    $this->get(route('account.orders.show', $order))
        ->assertSeeText('訂單已取消，庫存保留已釋出。')
        ->assertSeeText('已取消')
        ->assertDontSee(route('account.orders.cancel', $order), false);
});

it('returns the 庫存保留 only once when the 顧客 cancels the same 訂單 twice', function () {
    $order = orderWithItems($this->customer);

    $this->post(route('account.orders.cancel', $order));
    $this->post(route('account.orders.cancel', $order))
        ->assertRedirect(route('account.orders.show', $order))
        ->assertSessionHasErrors(['order' => '只有待付款的訂單可以取消。']);

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and(stockOf($order))->toBe([5, 6]);
});

dataset('訂單 that are not 待付款', [
    OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Completed, OrderStatus::PaymentException,
]);

it('refuses to cancel a 訂單 that is not 待付款', function (OrderStatus $status) {
    $order = orderWithItems($this->customer, ['status' => $status]);

    $this->get(route('account.orders.show', $order))
        ->assertDontSee(route('account.orders.cancel', $order), false);

    $this->post(route('account.orders.cancel', $order))
        ->assertSessionHasErrors(['order' => '只有待付款的訂單可以取消。']);

    expect($order->fresh()->status)->toBe($status)
        ->and(stockOf($order))->toBe([3, 5]);
})->with('訂單 that are not 待付款');

it('refuses to let a 顧客 cancel another 顧客\'s 訂單', function () {
    $someoneElses = orderWithItems(Customer::factory()->create());

    $this->post(route('account.orders.cancel', $someoneElses))->assertNotFound();

    expect($someoneElses->fresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and(stockOf($someoneElses))->toBe([3, 5]);
});

it('returns stock to the same 規格 for each 研磨方式 it was ordered with', function () {
    $variant = Variant::factory()->create(['stock' => 4]);
    $order = Order::factory()->for($this->customer)->create();
    $order->items()->createMany([
        ['variant_id' => $variant->id, 'grind' => Grind::Espresso, 'unit_price' => 420, 'quantity' => 2],
        ['variant_id' => $variant->id, 'grind' => Grind::WholeBean, 'unit_price' => 420, 'quantity' => 1],
    ]);

    $this->post(route('account.orders.cancel', $order));

    expect($variant->fresh()->stock)->toBe(7);
});

it('sends a 訪客 to the login page before showing or cancelling a 訂單', function () {
    $order = orderWithItems($this->customer);
    auth('customer')->logout();

    $this->get(route('account.orders.show', $order))->assertRedirect('/login');
    $this->post(route('account.orders.cancel', $order))->assertRedirect('/login');

    expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

dataset('訂單 that are no longer 待付款', [
    OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Completed, OrderStatus::Cancelled, OrderStatus::PaymentException,
]);

it('does not show the 付款期限 once the 訂單 is no longer 待付款', function (OrderStatus $status) {
    $order = orderWithItems($this->customer, ['status' => $status]);

    $this->get(route('account.orders.show', $order))
        ->assertOk()
        ->assertDontSeeText('付款期限')
        ->assertDontSee('data-payment-countdown', false);
})->with('訂單 that are no longer 待付款');

it('counts down to the 付款期限 and sends the 顧客 back to the 購物車 when it passes', function () {
    $this->freezeSecond();
    $order = orderWithItems($this->customer, ['payment_due_at' => now()->addMinutes(12)->addSeconds(5)]);

    $this->get(route('account.orders.show', $order))
        ->assertSee('data-payment-countdown', false)
        ->assertSee('data-seconds-left="725"', false)
        ->assertSee('data-expired-redirect="'.route('cart.show').'"', false);
});

it('starts the countdown at zero when the 付款期限 has already passed', function () {
    $order = orderWithItems($this->customer, ['payment_due_at' => now()->subMinute()]);

    $this->get(route('account.orders.show', $order))
        ->assertSee('data-seconds-left="0"', false);
});
