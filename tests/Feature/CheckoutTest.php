<?php

use App\Catalog\Grind;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Variant;
use App\Ordering\OrderStatus;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->customer = Customer::factory()->create([
        'recipient_name' => '林小美',
        'recipient_phone' => '0912-345-678',
        'recipient_address' => '台北市大安區復興南路一段 100 號',
    ]);
    $this->actingAs($this->customer, 'customer');
});

function beanVariant(string $name, int $price, int $stock, int $weight = 250): Variant
{
    return Variant::factory()
        ->for(Product::factory()->create(['name' => $name]))
        ->create(['weight_grams' => $weight, 'price' => $price, 'stock' => $stock]);
}

function addToCart(Variant $variant, int $quantity, string $grind = 'pour_over'): void
{
    test()->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => $grind, 'quantity' => $quantity])->assertOk();
}

it('shows the 訂單項目, 運費 and total on the checkout page, with the saved recipient info filled in', function () {
    addToCart(beanVariant('耶加雪菲 孔加 水洗', 420, 10), 2, 'espresso');

    $this->get('/checkout')
        ->assertOk()
        ->assertSeeText('耶加雪菲 孔加 水洗')
        ->assertSeeText('250g・義式')
        ->assertSeeText('× 2')
        ->assertSeeText('NT$840')
        ->assertSeeText('NT$100')
        ->assertSeeText('NT$940')
        ->assertSee('value="林小美"', false)
        ->assertSee('value="0912-345-678"', false)
        ->assertSee('value="台北市大安區復興南路一段 100 號"', false);
});

it('places a 待付款 訂單, reserves the stock and empties the 購物車', function () {
    $this->travelTo(now()->setDateTime(2026, 11, 13, 14, 0));
    $yirgacheffe = beanVariant('耶加雪菲 孔加 水洗', 420, 10);
    $huila = beanVariant('哥倫比亞 慧蘭 水洗', 380, 5, 100);
    addToCart($yirgacheffe, 2, 'espresso');
    addToCart($yirgacheffe, 1, 'whole_bean');
    addToCart($huila, 1);

    $response = $this->post('/checkout', [
        'recipient_name' => '王大明',
        'recipient_phone' => '02-2700-1234',
        'recipient_address' => '新北市板橋區文化路一段 1 號',
        'expected_total' => 1640,
    ]);

    $order = $this->customer->orders()->sole();
    $response->assertRedirect(route('checkout.placed', $order));

    expect($order)
        ->status->toBe(OrderStatus::PendingPayment)
        ->recipient_name->toBe('王大明')
        ->recipient_phone->toBe('02-2700-1234')
        ->recipient_address->toBe('新北市板橋區文化路一段 1 號')
        ->subtotal->toBe(1640)
        ->shipping_fee->toBe(0)
        ->total->toBe(1640)
        ->payment_due_at->toDateTimeString()->toBe('2026-11-13 14:30:00')
        ->and($order->items->map->only('variant_id', 'grind', 'unit_price', 'quantity')->all())->toBe([
            ['variant_id' => $yirgacheffe->id, 'grind' => Grind::Espresso, 'unit_price' => 420, 'quantity' => 2],
            ['variant_id' => $yirgacheffe->id, 'grind' => Grind::WholeBean, 'unit_price' => 420, 'quantity' => 1],
            ['variant_id' => $huila->id, 'grind' => Grind::PourOver, 'unit_price' => 380, 'quantity' => 1],
        ])
        ->and($yirgacheffe->fresh()->stock)->toBe(7)
        ->and($huila->fresh()->stock)->toBe(4);

    $this->getJson('/cart')->assertJsonPath('count', 0);
});

/**
 * 送出結帳，expected_total 預設為結帳頁上顯示的總計（與購物車總計相同）。
 */
function placeOrder(?int $expectedTotal = null): TestResponse
{
    return test()->from('/checkout')->post('/checkout', [
        'recipient_name' => '林小美',
        'recipient_phone' => '0912-345-678',
        'recipient_address' => '台北市大安區復興南路一段 100 號',
        'expected_total' => $expectedTotal ?? test()->getJson('/cart')->json('total'),
    ]);
}

it('places no 訂單 when the total changed after the 顧客 saw the checkout page', function () {
    $variant = beanVariant('耶加雪菲 孔加 水洗', 420, 10);
    addToCart($variant, 2);
    $this->get('/checkout')->assertSee('name="expected_total" value="940"', false);

    $variant->update(['price' => 450]);

    $this->followRedirects(placeOrder(940))
        ->assertSeeText('訂單金額已變動，請確認新的總計後再送出。')
        ->assertSeeText('NT$1,000');

    expect($this->customer->orders()->count())->toBe(0)
        ->and($variant->fresh()->stock)->toBe(10);

    placeOrder(1000)->assertRedirect(route('checkout.placed', $this->customer->orders()->sole()));
});

it('fails the whole 訂單 without reserving any stock when one 規格 is short, and says which', function () {
    $yirgacheffe = beanVariant('耶加雪菲 孔加 水洗', 420, 10);
    $huila = beanVariant('哥倫比亞 慧蘭 水洗', 380, 5, 100);
    addToCart($yirgacheffe, 2);
    addToCart($huila, 2, 'whole_bean');
    addToCart($huila, 2, 'espresso');
    $huila->update(['stock' => 3]);

    placeOrder()->assertRedirect('/checkout');

    $this->followRedirects(placeOrder())
        ->assertSeeText('庫存不足，訂單未成立。')
        ->assertSeeText('哥倫比亞 慧蘭 水洗 100g：庫存僅剩 3 包，購物車中共有 4 包。')
        ->assertDontSeeText('耶加雪菲 孔加 水洗 250g：');

    expect($this->customer->orders()->count())->toBe(0)
        ->and($yirgacheffe->fresh()->stock)->toBe(10)
        ->and($huila->fresh()->stock)->toBe(3);
    $this->getJson('/cart')->assertJsonPath('count', 6);
});

it('lets only the first of two 顧客 buy the last bag', function () {
    $lastBag = beanVariant('衣索比亞 古吉 日曬', 520, 1);
    $rival = Customer::factory()->create();
    addToCart($lastBag, 1);
    $this->actingAs($rival, 'customer');
    addToCart($lastBag, 1);

    $this->actingAs($this->customer, 'customer');
    placeOrder()->assertRedirect(route('checkout.placed', $this->customer->orders()->sole()));

    $this->actingAs($rival, 'customer');
    placeOrder()
        ->assertRedirect('/checkout')
        ->assertSessionHasErrors(['checkout' => '庫存不足，訂單未成立。']);

    expect($rival->orders()->count())->toBe(0)
        ->and($lastBag->fresh()->stock)->toBe(0);
});

it('places no 訂單 when the recipient info is missing', function () {
    $variant = beanVariant('耶加雪菲 孔加 水洗', 420, 10);
    addToCart($variant, 1);

    $this->from('/checkout')->post('/checkout', ['recipient_name' => '', 'recipient_phone' => 'abc', 'recipient_address' => ''])
        ->assertRedirect('/checkout')
        ->assertSessionHasErrors(['recipient_name', 'recipient_phone', 'recipient_address']);

    expect($this->customer->orders()->count())->toBe(0)
        ->and($variant->fresh()->stock)->toBe(10);
});

it('sends a 顧客 with an empty 購物車 back to the 購物車', function () {
    $this->get('/checkout')->assertRedirect('/cart');

    placeOrder()->assertSessionHasErrors('checkout');
    expect($this->customer->orders()->count())->toBe(0);
});

it('shows the 訂單 number and payment deadline to the 顧客 who placed it, and to no one else', function () {
    $this->travelTo(now()->setDateTime(2026, 11, 13, 6, 0)); // 台北時間 14:00
    addToCart(beanVariant('耶加雪菲 孔加 水洗', 420, 10), 1);
    placeOrder();
    $order = $this->customer->orders()->sole();

    expect($order->number)->toMatch('/^BR261113[0-9A-Z]{6}$/');

    $this->get(route('checkout.placed', $order))
        ->assertOk()
        ->assertSeeText($order->number)
        ->assertSeeText('待付款')
        ->assertSeeText('NT$520')
        ->assertSeeText('2026/11/13 14:30');

    $this->actingAs(Customer::factory()->create(), 'customer')
        ->get(route('checkout.placed', $order))
        ->assertNotFound();
});

it('sends the 顧客 straight on to 綠界 once the 訂單 is placed, but only the first time the page opens', function () {
    addToCart(beanVariant('耶加雪菲 孔加 水洗', 420, 10), 1);

    $this->followRedirects(placeOrder())
        ->assertSee("document.getElementById('start-payment').submit()", false);

    $this->get(route('checkout.placed', $this->customer->orders()->sole()))
        ->assertSeeText('前往綠界付款')
        ->assertDontSee("document.getElementById('start-payment').submit()", false);
});
