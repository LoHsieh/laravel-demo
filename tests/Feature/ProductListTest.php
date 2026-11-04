<?php

use App\Models\Product;
use App\Models\Variant;
use Database\Seeders\DatabaseSeeder;

it('shows a 訪客 the 咖啡豆 stored in the database with their 規格', function () {
    Product::factory()
        ->hasVariants(1, ['weight_grams' => 100, 'price' => 260, 'stock' => 12])
        ->hasVariants(1, ['weight_grams' => 250, 'price' => 580, 'stock' => 6])
        ->create([
            'name' => '耶加雪菲 孔加 水洗',
            'origin' => '衣索比亞',
            'process' => '水洗',
            'roast' => '淺焙',
            'flavor_description' => '茉莉・檸檬・紅茶',
        ]);

    $this->get('/')
        ->assertOk()
        ->assertSeeText('耶加雪菲 孔加 水洗')
        ->assertSeeText('衣索比亞')
        ->assertSeeText('水洗')
        ->assertSeeText('淺焙')
        ->assertSeeText('茉莉・檸檬・紅茶')
        ->assertSeeTextInOrder(['100g', 'NT$260', '250g', 'NT$580']);
});

it('lists each 規格 from lightest to heaviest', function () {
    Product::factory()
        ->hasVariants(1, ['weight_grams' => 500, 'price' => 1080])
        ->hasVariants(1, ['weight_grams' => 100, 'price' => 260])
        ->create();

    $this->get('/')->assertSeeTextInOrder(['100g', '500g']);
});

it('does not show 咖啡豆 that are not published', function () {
    Product::factory()->create(['name' => '已下架的豆子', 'is_published' => false]);

    $this->get('/')->assertOk()->assertDontSeeText('已下架的豆子');
});

it('lists the seeded 咖啡豆 and their 規格', function () {
    $this->seed(DatabaseSeeder::class);

    $this->get('/')
        ->assertOk()
        ->assertSeeText('耶加雪菲 孔加 水洗')
        ->assertSeeText('肯亞 涅里 AA 水洗')
        ->assertSeeText('哥倫比亞 慧蘭 水洗')
        ->assertSeeText('瓜地馬拉 安提瓜 日曬')
        ->assertSeeTextInOrder(['100g', 'NT$260', '250g', 'NT$580', '500g', 'NT$1,080']);
});

it('does not duplicate 咖啡豆 or 規格 when the seeder runs again on the next deploy', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Product::count())->toBe(4)
        ->and(Variant::count())->toBe(10);
});

it('embeds the Vite-bundled Vue component with the 規格 as its props', function () {
    Product::factory()
        ->hasVariants(1, ['weight_grams' => 100, 'price' => 260])
        ->create();

    $this->get('/')
        ->assertSee('data-vue-component="VariantPrices"', escape: false)
        ->assertSee('"weight_grams":100')
        ->assertSee('/build/assets/app-', escape: false);
});
