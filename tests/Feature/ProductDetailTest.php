<?php

use App\Models\FlavorNote;
use App\Models\Product;

it('shows the 產地, 處理法, 烘焙度, 風味標籤 and 風味描述 of a 咖啡豆', function () {
    $product = Product::factory()
        ->hasAttached([
            FlavorNote::factory()->create(['name' => '花香']),
            FlavorNote::factory()->create(['name' => '果酸']),
        ])
        ->create([
            'name' => '耶加雪菲 孔加 水洗',
            'origin' => '衣索比亞',
            'process' => '水洗',
            'roast' => '淺焙',
            'flavor_description' => '茉莉花香明顯，放涼後轉為檸檬與紅茶般的清甜。',
        ]);

    $this->get("/beans/{$product->id}")
        ->assertOk()
        ->assertSeeTextInOrder([
            'ETHIOPIA',
            '耶加雪菲 孔加 水洗',
            '茉莉花香明顯，放涼後轉為檸檬與紅茶般的清甜。',
            '花香',
            '果酸',
            '產地', '衣索比亞',
            '處理法', '水洗',
            '烘焙度', '淺焙',
        ])
        ->assertSee('烘焙度 5 級中的第 1 級');
});

/**
 * 解析詳情頁交給 Vue 購買區元件的初始資料。
 *
 * @return array<string, mixed>
 */
function purchaseProps(Product $product): array
{
    $content = test()->get("/beans/{$product->id}")
        ->assertOk()
        ->assertSee('data-vue-component="ProductPurchase"', escape: false)
        ->getContent();

    preg_match('/data-vue-component="ProductPurchase"\s+data-props="([^"]*)"/', $content, $matches);

    return json_decode(html_entity_decode($matches[1] ?? ''), true, flags: JSON_THROW_ON_ERROR);
}

it('offers each 規格 by weight with its own price and stock, marking sold-out 規格 as unavailable', function () {
    $product = Product::factory()
        ->hasVariants(1, ['weight_grams' => 500, 'price' => 1080, 'stock' => 0])
        ->hasVariants(1, ['weight_grams' => 100, 'price' => 260, 'stock' => 12])
        ->hasVariants(1, ['weight_grams' => 250, 'price' => 580, 'stock' => 6])
        ->create();

    $variants = collect(purchaseProps($product)['variants'])
        ->map(fn (array $variant) => array_intersect_key($variant, array_flip(['label', 'price', 'stock', 'sold_out'])))
        ->all();

    expect($variants)->toBe([
        ['label' => '100g', 'price' => 260, 'stock' => 12, 'sold_out' => false],
        ['label' => '250g', 'price' => 580, 'stock' => 6, 'sold_out' => false],
        ['label' => '500g', 'price' => 1080, 'stock' => 0, 'sold_out' => true],
    ]);
});

it('offers the fixed 研磨方式 options for every 咖啡豆', function () {
    $grinds = purchaseProps(Product::factory()->create())['grinds'];

    expect(array_column($grinds, 'label'))->toBe(['原豆', '手沖', '義式', '法式濾壓']);
});

it('hides unpublished 咖啡豆', function () {
    $product = Product::factory()->create(['is_published' => false]);

    $this->get("/beans/{$product->id}")->assertNotFound();
});

it('links 咖啡豆 cards on the home page and the list to their detail page', function () {
    $product = Product::factory()->create(['is_featured' => true]);
    $url = route('products.show', $product);

    $this->get('/')->assertOk()->assertSee('href="'.$url.'"', escape: false);

    expect($this->getJson('/beans')->assertOk()->json('products.0.url'))->toBe($url);
});

it('recommends other published 咖啡豆 sharing a 風味標籤, most shared 風味標籤 first', function () {
    [$floral, $fruity, $nutty] = collect(['花香', '果酸', '堅果'])
        ->map(fn (string $name) => FlavorNote::factory()->create(['name' => $name]));

    $product = Product::factory()->hasAttached([$floral, $fruity])->create(['name' => '本頁咖啡豆']);
    Product::factory()->hasAttached($fruity)->create(['name' => '只有果酸']);
    Product::factory()->hasAttached([$floral, $fruity])->create(['name' => '花香果酸']);
    Product::factory()->hasAttached($nutty)->create(['name' => '只有堅果']);
    Product::factory()->hasAttached($floral)->create(['name' => '下架的花香', 'is_published' => false]);

    $this->get("/beans/{$product->id}")
        ->assertOk()
        ->assertSeeTextInOrder(['你可能也喜歡', '共 2 支', '花香果酸', '只有果酸'])
        ->assertDontSeeText('只有堅果')
        ->assertDontSeeText('下架的花香');
});
