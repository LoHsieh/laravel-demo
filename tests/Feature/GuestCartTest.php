<?php

use App\Models\Product;
use App\Models\Variant;

it('lets a 訪客 add a 規格 with a 研磨方式 to the 購物車 without logging in', function () {
    $product = Product::factory()->create(['name' => '耶加雪菲 孔加 水洗', 'origin' => '衣索比亞']);
    $variant = Variant::factory()->for($product)->create(['weight_grams' => 250, 'price' => 580, 'stock' => 6]);

    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 2])
        ->assertOk()
        ->assertJsonPath('count', 2);

    $item = $this->getJson('/cart')->assertOk()->json('items.0');

    expect($item)->toMatchArray([
        'name' => '耶加雪菲 孔加 水洗',
        'origin_latin' => 'ETHIOPIA',
        'variant_label' => '250g',
        'grind_label' => '手沖',
        'price' => 580,
        'quantity' => 2,
        'stock' => 6,
        'line_total' => 1160,
    ]);
});

it('adds up the quantity when the same 規格 and 研磨方式 is added again', function () {
    $variant = Variant::factory()->create(['stock' => 10]);

    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'espresso', 'quantity' => 1]);
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'espresso', 'quantity' => 2])
        ->assertJsonPath('count', 3);

    $items = $this->getJson('/cart')->json('items');

    expect($items)->toHaveCount(1)
        ->and($items[0]['quantity'])->toBe(3);
});

it('keeps the same 規格 with a different 研磨方式 as a separate 購物車項目', function () {
    $variant = Variant::factory()->create(['stock' => 10]);

    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'whole_bean', 'quantity' => 1]);
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 1]);

    $items = $this->getJson('/cart')->json('items');

    expect(array_column($items, 'grind_label'))->toBe(['原豆', '手沖'])
        ->and(array_column($items, 'quantity'))->toBe([1, 1]);
});

it('refuses to add more than the 規格 has in stock, counting what is already in the 購物車', function () {
    $variant = Variant::factory()->create(['stock' => 3]);

    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'whole_bean', 'quantity' => 2])->assertOk();
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 2])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity' => '此規格目前僅剩 3 包，購物車中已有 2 包。']);

    expect($this->getJson('/cart')->json('count'))->toBe(2);
});

it('does not add sold-out 規格 or 規格 of unpublished 咖啡豆', function () {
    $soldOut = Variant::factory()->create(['stock' => 0]);
    $unpublished = Variant::factory()
        ->for(Product::factory()->create(['is_published' => false]))
        ->create();

    $this->postJson('/cart/items', ['variant_id' => $soldOut->id, 'grind' => 'whole_bean', 'quantity' => 1])
        ->assertUnprocessable();
    $this->postJson('/cart/items', ['variant_id' => $unpublished->id, 'grind' => 'whole_bean', 'quantity' => 1])
        ->assertNotFound();

    expect($this->getJson('/cart')->json('count'))->toBe(0);
});

it('lets a 訪客 change the quantity of a 購物車項目, up to the stock of the 規格', function () {
    $variant = Variant::factory()->create(['stock' => 4]);
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 1]);
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'espresso', 'quantity' => 1]);
    $key = $this->getJson('/cart')->json('items.0.key');

    $this->patchJson("/cart/items/{$key}", ['quantity' => 3])
        ->assertOk()
        ->assertJsonPath('items.0.quantity', 3)
        ->assertJsonPath('count', 4);

    $this->patchJson("/cart/items/{$key}", ['quantity' => 4])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity' => '此規格目前僅剩 4 包，購物車中其他研磨方式已有 1 包。']);

    expect($this->getJson('/cart')->json('items.0.quantity'))->toBe(3);
});

it('lets a 訪客 remove a 購物車項目', function () {
    $variant = Variant::factory()->create();
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 1]);
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'espresso', 'quantity' => 2]);
    $key = $this->getJson('/cart')->json('items.0.key');

    $this->deleteJson("/cart/items/{$key}")
        ->assertOk()
        ->assertJsonPath('count', 2)
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.grind_label', '義式');
});

it('answers not found for a 購物車項目 that is not in the 購物車', function () {
    $this->patchJson('/cart/items/999-pour_over', ['quantity' => 1])->assertNotFound();
    $this->deleteJson('/cart/items/999-pour_over')->assertNotFound();
});

it('charges NT$100 運費 below the 免運門檻 and says how much more is needed', function () {
    $variant = Variant::factory()->create(['price' => 333, 'stock' => 10]);
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 3]);

    $this->getJson('/cart')->assertJson([
        'subtotal' => 999,
        'shipping_fee' => 100,
        'total' => 1099,
        'free_shipping_remaining' => 1,
    ]);
});

it('waives the 運費 when the subtotal is exactly NT$1,000', function () {
    $variant = Variant::factory()->create(['price' => 500, 'stock' => 10]);
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'whole_bean', 'quantity' => 1]);
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'espresso', 'quantity' => 1]);

    $this->getJson('/cart')->assertJson([
        'subtotal' => 1000,
        'shipping_fee' => 0,
        'total' => 1000,
        'free_shipping_remaining' => 0,
    ]);
});

it('shows the number of items in the 購物車 in the page header', function () {
    $variant = Variant::factory()->create();

    $this->get('/beans')->assertOk()->assertSee('aria-label="購物車，0 件商品"', escape: false);

    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 2]);
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'espresso', 'quantity' => 1]);

    $this->get('/beans')->assertOk()->assertSee('aria-label="購物車，3 件商品"', escape: false);
});

it('guides a 訪客 with an empty 購物車 back to the 咖啡豆', function () {
    $this->get('/cart')
        ->assertOk()
        ->assertSeeText('購物車還是空的')
        ->assertSee('href="'.route('products.index').'"', escape: false)
        ->assertSeeText('選購咖啡豆');
});

it('renders the 購物車 page with its 購物車項目 for the cart component', function () {
    $variant = Variant::factory()->for(Product::factory()->create(['name' => '哥倫比亞 慧蘭 水洗']))->create();
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'whole_bean', 'quantity' => 1]);

    $content = $this->get('/cart')
        ->assertOk()
        ->assertDontSeeText('購物車還是空的')
        ->getContent();

    preg_match('/data-vue-component="CartPage"\s+data-props="([^"]*)"/', $content, $matches);
    $props = json_decode(html_entity_decode($matches[1] ?? ''), true, flags: JSON_THROW_ON_ERROR);

    expect($props['cart']['items'][0]['name'])->toBe('哥倫比亞 慧蘭 水洗')
        ->and($props['cart']['total'])->toBe(360);
});
