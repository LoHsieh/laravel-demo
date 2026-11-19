<?php

use App\Catalog\Grind;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Variant;
use App\Ordering\OrderStatus;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->actingAs(Admin::factory()->create(), 'admin');
    // 店家時區（台北）2026/11/19 下午三點
    $this->travelTo(Carbon::parse('2026-11-19 15:00', 'Asia/Taipei'));
});

function orderPlacedAt(string $taipeiTime, OrderStatus $status, int $total): Order
{
    return Order::factory()->create([
        'status' => $status,
        'total' => $total,
        // 資料庫裡的時間是 UTC（app.timezone），和正式下單時一樣
        'created_at' => Carbon::parse($taipeiTime, 'Asia/Taipei')->utc(),
    ]);
}

it('shows today\'s 營收 and 訂單數 on the 後台 sales overview', function () {
    orderPlacedAt('2026-11-19 09:00', OrderStatus::Paid, 940);
    orderPlacedAt('2026-11-19 14:00', OrderStatus::Shipped, 1200);

    $this->get('/admin')
        ->assertOk()
        ->assertSeeTextInOrder(['今日', '營收', 'NT$2,140', '訂單數', '2 筆']);
});

it('shows this month\'s 營收 and 訂單數 next to today\'s', function () {
    orderPlacedAt('2026-11-19 09:00', OrderStatus::Paid, 940);
    orderPlacedAt('2026-11-01 00:10', OrderStatus::Completed, 1200);
    orderPlacedAt('2026-10-31 23:50', OrderStatus::Completed, 5000);
    orderPlacedAt('2026-11-10 12:00', OrderStatus::Cancelled, 3000);

    $this->get('/admin')
        ->assertOk()
        ->assertSeeTextInOrder(['今日', '營收', 'NT$940', '訂單數', '1 筆'])
        ->assertSeeTextInOrder(['本月', '營收', 'NT$2,140', '訂單數', '2 筆']);
});

it('counts 今日 by the 店家 timezone, not UTC', function () {
    // 台北 00:30 是 UTC 前一天 16:30；台北前一天 23:30 是 UTC 當天之前
    orderPlacedAt('2026-11-19 00:30', OrderStatus::Paid, 500);
    orderPlacedAt('2026-11-18 23:30', OrderStatus::Paid, 7000);

    $this->get('/admin')
        ->assertOk()
        ->assertSeeTextInOrder(['今日', '營收', 'NT$500', '訂單數', '1 筆']);
});

it('leaves 待付款, 已取消 and 付款異常 訂單 out of the 營收 and 訂單數', function () {
    orderPlacedAt('2026-11-19 09:00', OrderStatus::Completed, 940);
    orderPlacedAt('2026-11-19 10:00', OrderStatus::PendingPayment, 1000);
    orderPlacedAt('2026-11-19 11:00', OrderStatus::Cancelled, 2000);
    orderPlacedAt('2026-11-19 12:00', OrderStatus::PaymentException, 3000);

    $this->get('/admin')
        ->assertOk()
        ->assertSeeTextInOrder(['今日', '營收', 'NT$940', '訂單數', '1 筆']);
});

function sell(Order $order, Variant $variant, int $quantity): void
{
    $order->items()->create([...OrderItem::snapshotOf($variant), 'grind' => Grind::WholeBean, 'unit_price' => 420, 'quantity' => $quantity]);
}

it('ranks this month\'s 熱銷咖啡豆 by how many bags were sold across their 規格', function () {
    $yirgacheffe = Product::factory()->create(['name' => '耶加雪菲 孔加 水洗']);
    $huila = Product::factory()->create(['name' => '哥倫比亞 薇拉 蜜處理']);
    $guji = Product::factory()->create(['name' => '古吉 罕貝拉 日曬']);

    $order = orderPlacedAt('2026-11-19 09:00', OrderStatus::Paid, 940);
    sell($order, Variant::factory()->for($huila)->create(['weight_grams' => 250]), 2);
    sell($order, Variant::factory()->for($yirgacheffe)->create(['weight_grams' => 100]), 1);
    sell(orderPlacedAt('2026-11-02 09:00', OrderStatus::Completed, 940), Variant::factory()->for($huila)->create(['weight_grams' => 500]), 1);
    sell(orderPlacedAt('2026-11-03 09:00', OrderStatus::Shipped, 940), $yirgacheffe->variants()->first(), 1);

    // 不計入營收的訂單與上個月的訂單都不算
    sell(orderPlacedAt('2026-11-04 09:00', OrderStatus::Cancelled, 940), Variant::factory()->for($guji)->create(), 9);
    sell(orderPlacedAt('2026-10-31 09:00', OrderStatus::Completed, 940), $guji->variants()->first(), 9);

    $this->get('/admin')
        ->assertOk()
        ->assertSeeTextInOrder(['熱銷咖啡豆', '哥倫比亞 薇拉 蜜處理', '3 包', '耶加雪菲 孔加 水洗', '2 包'])
        ->assertDontSeeText('古吉 罕貝拉 日曬');
});
