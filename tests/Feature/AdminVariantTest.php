<?php

use App\Catalog\Grind;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Variant;
use App\Ordering\OrderStatus;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actingAs(Admin::factory()->create(), 'admin');
    $this->product = Product::factory()->create(['name' => '耶加雪菲 孔加 水洗']);
});

/**
 * 顧客買了 $variant × 2 的待付款訂單（庫存已保留）。
 */
function orderOf(Variant $variant, Customer $customer): Order
{
    $order = Order::factory()->for($customer)->create(['subtotal' => 840, 'shipping_fee' => 100, 'total' => 940]);
    $order->items()->create([...OrderItem::snapshotOf($variant), 'grind' => Grind::PourOver, 'unit_price' => 420, 'quantity' => 2]);

    return $order;
}

it('lets a 店家 add a 規格 with its price and 庫存 to a 咖啡豆', function () {
    $this->post("/admin/products/{$this->product->id}/variants", ['weight_grams' => 250, 'price' => 420, 'stock' => 12])
        ->assertRedirect("/admin/products/{$this->product->id}/edit");

    expect($this->product->variants()->sole())
        ->weight_grams->toBe(250)
        ->price->toBe(420)
        ->stock->toBe(12);

    $this->getJson('/beans')->assertJsonPath('products.0.min_price', 420);
    $this->get("/admin/products/{$this->product->id}/edit")->assertOk()->assertSee('250g');
});

it('rejects a 規格 whose weight the 咖啡豆 already has, or with an invalid price or 庫存', function () {
    Variant::factory()->for($this->product)->create(['weight_grams' => 250]);

    $this->post("/admin/products/{$this->product->id}/variants", ['weight_grams' => 250, 'price' => 0, 'stock' => -1])
        ->assertSessionHasErrors(['weight_grams', 'price', 'stock'], errorBag: 'newVariant');

    expect($this->product->variants()->count())->toBe(1);
});

it('lets a 店家 change the price and adjust the 庫存 of a 規格', function () {
    $variant = Variant::factory()->for($this->product)->create(['weight_grams' => 250, 'price' => 420, 'stock' => 3]);

    $this->patch("/admin/variants/{$variant->id}", ['price' => 450, 'stock' => 20])
        ->assertRedirect("/admin/products/{$this->product->id}/edit");

    expect($variant->fresh())->price->toBe(450)->stock->toBe(20);

    $this->patch("/admin/variants/{$variant->id}", ['price' => 450, 'stock' => -1])
        ->assertSessionHasErrors('stock', errorBag: "variant{$variant->id}");
    expect($variant->fresh()->stock)->toBe(20);
});

it('takes a deleted 規格 off the 前台 and out of the 購物車, but keeps it on existing 訂單', function () {
    $deleted = Variant::factory()->for($this->product)->create(['weight_grams' => 100, 'price' => 200, 'stock' => 5]);
    Variant::factory()->for($this->product)->create(['weight_grams' => 250, 'price' => 420, 'stock' => 5]);
    $customer = Customer::factory()->create();
    $order = orderOf($deleted, $customer);
    $this->actingAs($customer, 'customer');
    $this->postJson('/cart/items', ['variant_id' => $deleted->id, 'grind' => 'whole_bean', 'quantity' => 1])->assertOk();

    $this->delete("/admin/variants/{$deleted->id}")
        ->assertRedirect("/admin/products/{$this->product->id}/edit")
        ->assertSessionHas('status');

    expect(Variant::withTrashed()->find($deleted->id)->trashed())->toBeTrue();
    $this->getJson('/beans')->assertJsonPath('products.0.min_price', 420);
    $this->get("/beans/{$this->product->id}")->assertDontSee('100g');
    $this->getJson('/cart')->assertJsonCount(0, 'items');
    $this->postJson('/cart/items', ['variant_id' => $deleted->id, 'grind' => 'whole_bean', 'quantity' => 1])->assertNotFound();
    $this->get("/admin/products/{$this->product->id}/edit")->assertDontSee('100g');
    $this->get("/account/orders/{$order->number}")->assertOk()->assertSeeText('耶加雪菲 孔加 水洗')->assertSeeText('100g');
});

it('places no 訂單 for a 規格 deleted while it was in the 購物車', function () {
    $variant = Variant::factory()->for($this->product)->create(['price' => 420, 'stock' => 5]);
    $customer = Customer::factory()->create(['recipient_name' => '林小美']);
    $this->actingAs($customer, 'customer');
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'whole_bean', 'quantity' => 1]);
    $this->delete("/admin/variants/{$variant->id}");

    $this->post('/checkout', [
        'recipient_name' => '林小美',
        'recipient_phone' => '0912-345-678',
        'recipient_address' => '台北市大安區復興南路一段 100 號',
        'expected_total' => 520,
    ])->assertSessionHasErrors('checkout');

    expect($customer->orders()->count())->toBe(0)
        ->and(Variant::withTrashed()->find($variant->id)->stock)->toBe(5);
});

it('returns the 庫存保留 to a deleted 規格 when its 訂單 is cancelled', function () {
    $variant = Variant::factory()->for($this->product)->create(['stock' => 3]);
    $customer = Customer::factory()->create();
    $order = orderOf($variant, $customer);
    $this->delete("/admin/variants/{$variant->id}");

    $this->actingAs($customer, 'customer')->post("/account/orders/{$order->number}/cancel");

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and(Variant::withTrashed()->find($variant->id)->stock)->toBe(5);
});

it('brings a deleted 規格 back when the 店家 adds the same weight again', function () {
    $variant = Variant::factory()->for($this->product)->create(['weight_grams' => 250, 'price' => 420, 'stock' => 3]);
    $order = orderOf($variant, Customer::factory()->create());
    $this->delete("/admin/variants/{$variant->id}");

    $this->post("/admin/products/{$this->product->id}/variants", ['weight_grams' => 250, 'price' => 480, 'stock' => 8])
        ->assertSessionHasNoErrors();

    expect($this->product->variants()->sole())
        ->id->toBe($variant->id)
        ->price->toBe(480)
        ->stock->toBe(8)
        ->and($order->items()->sole()->variant->is($variant))->toBeTrue();
});

it('says the 規格 already has 訂單 instead of failing when the database refuses to delete it', function () {
    $variant = Variant::factory()->for($this->product)->create(['weight_grams' => 250]);
    orderOf($variant, Customer::factory()->create());
    // 模擬有程式繞過軟刪除、真的刪除資料列：訂單項目的外鍵讓 MySQL 拒絕（1451）
    Variant::deleting(fn (Variant $variant) => DB::table('variants')->where('id', $variant->id)->delete());

    $this->delete("/admin/variants/{$variant->id}")
        ->assertRedirect("/admin/products/{$this->product->id}/edit")
        ->assertSessionHasErrors(['variant' => '此規格已有訂單，無法刪除。']);

    expect(Variant::find($variant->id))->not->toBeNull();
});
