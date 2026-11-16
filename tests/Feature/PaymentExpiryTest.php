<?php

use App\Catalog\Grind;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Variant;
use App\Ordering\ExpireOrder;
use App\Ordering\InvalidOrderTransition;
use App\Ordering\OrderStatus;

beforeEach(function () {
    $this->travelTo(now()->setDateTime(2026, 11, 13, 6, 0)); // 台北時間 14:00，付款期限 14:30
    $this->customer = Customer::factory()->create();
    $this->actingAs($this->customer, 'customer');

    $this->yirgacheffe = Variant::factory()->for(Product::factory()->create(['name' => '耶加雪菲 孔加 水洗']))
        ->create(['weight_grams' => 250, 'price' => 420, 'stock' => 3]);
    $this->huila = Variant::factory()->for(Product::factory()->create(['name' => '哥倫比亞 慧蘭 水洗']))
        ->create(['weight_grams' => 100, 'price' => 380, 'stock' => 5]);
});

/**
 * 剛成立的待付款訂單（庫存已保留）：耶加雪菲 250g 義式 × 2、慧蘭 100g 原豆 × 1。
 */
function placedOrder(Customer $customer): Order
{
    $order = Order::factory()->for($customer)->create(['subtotal' => 1220, 'shipping_fee' => 0, 'total' => 1220]);
    $order->items()->createMany([
        ['variant_id' => test()->yirgacheffe->id, 'grind' => Grind::Espresso, 'unit_price' => 420, 'quantity' => 2],
        ['variant_id' => test()->huila->id, 'grind' => Grind::WholeBean, 'unit_price' => 380, 'quantity' => 1],
    ]);

    return $order;
}

/**
 * 顧客購物車中的 [key, 數量]，依加入順序。
 *
 * @return list<array{string, int}>
 */
function cartLines(): array
{
    return array_map(
        fn (array $item) => [$item['key'], $item['quantity']],
        test()->getJson('/cart')->json('items'),
    );
}

function runScheduler(): void
{
    test()->artisan('schedule:run')->assertSuccessful();
}

it('cancels a 待付款 訂單 past its 付款期限 on the scheduler, returns its 庫存保留 and puts its 訂單項目 back in the 購物車', function () {
    $order = placedOrder($this->customer);

    $this->travel(31)->minutes();
    runScheduler();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($this->yirgacheffe->fresh()->stock)->toBe(5)
        ->and($this->huila->fresh()->stock)->toBe(6)
        ->and(cartLines())->toBe([
            ["{$this->yirgacheffe->id}-espresso", 2],
            ["{$this->huila->id}-whole_bean", 1],
        ]);
});

it('returns the 庫存保留 and fills the 購物車 only once when two requests expire the same 訂單 at the same time', function () {
    $order = placedOrder($this->customer);
    $this->travel(31)->minutes();
    // 排程與顧客查看訂單各自讀到待付款的同一筆訂單
    $seenByScheduler = Order::find($order->id);
    $seenByCustomer = Order::find($order->id);

    app(ExpireOrder::class)($seenByScheduler);

    expect(fn () => app(ExpireOrder::class)($seenByCustomer))->toThrow(InvalidOrderTransition::class)
        ->and($this->yirgacheffe->fresh()->stock)->toBe(5)
        ->and($this->huila->fresh()->stock)->toBe(6)
        ->and(cartLines())->toBe([
            ["{$this->yirgacheffe->id}-espresso", 2],
            ["{$this->huila->id}-whole_bean", 1],
        ]);
});

it('does not put the 訂單項目 back in the 購物車 when the 顧客 cancels the 訂單', function () {
    $order = placedOrder($this->customer);

    $this->post(route('account.orders.cancel', $order));
    $this->travel(31)->minutes();
    runScheduler();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($this->yirgacheffe->fresh()->stock)->toBe(5)
        ->and(cartLines())->toBe([]);
});

it('adds the 訂單項目 to what is already in the 購物車, capped at the stock, and says so on the 訂單', function () {
    $order = placedOrder($this->customer);
    // 下單後顧客又把耶加雪菲加進購物車（庫存剩 3），之後別人買走 1 包
    $this->postJson('/cart/items', ['variant_id' => $this->yirgacheffe->id, 'grind' => 'espresso', 'quantity' => 3])->assertOk();
    $this->yirgacheffe->decrement('stock');

    $this->travel(31)->minutes();
    runScheduler();

    // 歸還 2 包後庫存為 4：購物車原有 3 包 + 訂單 2 包，調整為 4 包
    expect(cartLines())->toBe([
        ["{$this->yirgacheffe->id}-espresso", 4],
        ["{$this->huila->id}-whole_bean", 1],
    ]);

    $this->get(route('account.orders.show', $order))
        ->assertSeeText('已取消')
        ->assertSeeText('付款逾期，訂單已取消，訂單項目已放回購物車。')
        ->assertSeeText('耶加雪菲 孔加 水洗 250g・義式：庫存僅剩 4 包，合併後調整為 4 包。');
});

it('skips a 規格 that is no longer for sale and says so on the 訂單', function () {
    $order = placedOrder($this->customer);
    $this->huila->product->update(['is_published' => false]);

    $this->travel(31)->minutes();
    runScheduler();

    expect(cartLines())->toBe([["{$this->yirgacheffe->id}-espresso", 2]])
        ->and($this->huila->fresh()->stock)->toBe(6);

    $this->get(route('account.orders.show', $order))
        ->assertSeeText('哥倫比亞 慧蘭 水洗 100g・原豆：已下架，未併入購物車。');
});

it('shows the price the 顧客 ordered at and flags a price change in the 購物車', function () {
    placedOrder($this->customer);
    $this->huila->update(['price' => 400]);

    $this->travel(31)->minutes();
    runScheduler();

    $this->getJson('/cart')
        ->assertJsonPath('items.0.notice', null)
        ->assertJsonPath('items.1.notice', '價格已由 NT$380 調整為 NT$400。');
});

it('does not show the 放回購物車 note on a 訂單 the 顧客 cancelled', function () {
    $order = placedOrder($this->customer);

    $this->post(route('account.orders.cancel', $order));

    $this->get(route('account.orders.show', $order))->assertDontSeeText('付款逾期');
});

it('leaves a 訂單 alone on the scheduler before its 付款期限', function () {
    $order = placedOrder($this->customer);

    $this->travel(29)->minutes();
    runScheduler();

    expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and($this->yirgacheffe->fresh()->stock)->toBe(3)
        ->and(cartLines())->toBe([]);
});

describe('when the scheduler has not run yet (the site was asleep)', function () {
    it('shows an overdue 訂單 as 已取消 when the 顧客 views it, with its 訂單項目 back in the 購物車', function () {
        $order = placedOrder($this->customer);
        $this->travel(31)->minutes();

        $this->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertSeeText('已取消')
            ->assertSeeText('付款逾期，訂單已取消，訂單項目已放回購物車。')
            ->assertDontSee(route('account.orders.cancel', $order), false);

        expect($this->yirgacheffe->fresh()->stock)->toBe(5)
            ->and(cartLines())->toBe([
                ["{$this->yirgacheffe->id}-espresso", 2],
                ["{$this->huila->id}-whole_bean", 1],
            ]);
    });

    it('shows an overdue 訂單 as 已取消 in the 會員中心 list', function () {
        $order = placedOrder($this->customer);
        $this->travel(31)->minutes();

        $this->get('/account')->assertOk()->assertSeeTextInOrder([$order->number, '已取消']);
    });

    it('shows an overdue 訂單 as 已取消 on the payment result page', function () {
        $order = placedOrder($this->customer);
        $this->travel(31)->minutes();

        $this->get(route('payments.show', $order))->assertOk()->assertSeeText('訂單已取消');
    });

    it('puts the 訂單項目 back in the 購物車 when the countdown sends the 顧客 to the 購物車', function () {
        $order = placedOrder($this->customer);
        $this->travel(31)->minutes();

        $this->get('/cart')->assertOk();

        expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
            ->and(cartLines())->toHaveCount(2);
    });

    it('does not expire another 顧客\'s 訂單 when someone views their own', function () {
        $someoneElses = placedOrder(Customer::factory()->create());
        $this->travel(31)->minutes();

        $this->get('/account')->assertOk();
        $this->get('/cart')->assertOk();

        expect($someoneElses->fresh()->status)->toBe(OrderStatus::PendingPayment);
    });
});
