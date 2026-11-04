<?php

use App\Models\Product;

it('features the most recently featured 咖啡豆 on the home page', function () {
    Product::factory()->create(['name' => '較早的精選', 'is_featured' => true, 'created_at' => now()->subWeek()]);
    Product::factory()->create(['name' => '耶加雪菲 孔加', 'is_featured' => true, 'origin' => '衣索比亞', 'roast' => '淺焙']);
    Product::factory()->create(['name' => '下架的精選', 'is_featured' => true, 'is_published' => false, 'created_at' => now()->addDay()]);

    $this->get('/')
        ->assertOk()
        ->assertSeeTextInOrder(['ETHIOPIA', 'LIGHT ROAST', '本週精選：耶加雪菲 孔加', '本週烘焙'])
        ->assertDontSeeText('下架的精選');
});

it('shows the eight newest published 咖啡豆 with their lowest price and sold-out state', function () {
    foreach (range(1, 9) as $day) {
        Product::factory()
            ->hasVariants(1, ['weight_grams' => 100, 'price' => 200 + $day, 'stock' => $day === 9 ? 0 : 5])
            ->create(['name' => "第 {$day} 天上架", 'created_at' => now()->subDays(10 - $day)]);
    }
    Product::factory()->create(['name' => '已下架', 'is_published' => false]);

    $this->get('/')
        ->assertOk()
        ->assertSeeTextInOrder(['本週烘焙', '已售完', '第 9 天上架', 'NT$209 起', '第 8 天上架', 'NT$208 起', '第 2 天上架'])
        ->assertDontSeeText('第 1 天上架')
        ->assertDontSeeText('已下架');
});

it('links each 風味標籤 shortcut to the filtered 咖啡豆 list', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(e(route('products.index', ['flavor' => ['花香']])), escape: false)
        ->assertSee(e(route('products.index', ['flavor' => ['巧克力']])), escape: false);
});
