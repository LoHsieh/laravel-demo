<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->products() as [$attributes, $variants]) {
            Product::create($attributes)->variants()->createMany($variants);
        }
    }

    /**
     * @return list<array{array<string, string>, list<array<string, int>>}>
     */
    private function products(): array
    {
        return [
            [
                ['name' => '耶加雪菲 孔加 水洗', 'origin' => '衣索比亞', 'process' => '水洗', 'roast' => '淺焙', 'flavor_description' => '茉莉・檸檬・紅茶'],
                [
                    ['weight_grams' => 100, 'price' => 260, 'stock' => 12],
                    ['weight_grams' => 250, 'price' => 580, 'stock' => 6],
                    ['weight_grams' => 500, 'price' => 1080, 'stock' => 0],
                ],
            ],
            [
                ['name' => '肯亞 涅里 AA 水洗', 'origin' => '肯亞', 'process' => '水洗', 'roast' => '淺中焙', 'flavor_description' => '黑醋栗・葡萄柚・蔗糖'],
                [
                    ['weight_grams' => 100, 'price' => 320, 'stock' => 10],
                    ['weight_grams' => 250, 'price' => 720, 'stock' => 8],
                ],
            ],
            [
                ['name' => '哥倫比亞 慧蘭 水洗', 'origin' => '哥倫比亞', 'process' => '水洗', 'roast' => '中焙', 'flavor_description' => '焦糖・蘋果・杏仁'],
                [
                    ['weight_grams' => 100, 'price' => 240, 'stock' => 15],
                    ['weight_grams' => 250, 'price' => 540, 'stock' => 9],
                    ['weight_grams' => 500, 'price' => 1000, 'stock' => 4],
                ],
            ],
            [
                ['name' => '瓜地馬拉 安提瓜 日曬', 'origin' => '瓜地馬拉', 'process' => '日曬', 'roast' => '中深焙', 'flavor_description' => '可可・黑糖・烤堅果'],
                [
                    ['weight_grams' => 100, 'price' => 240, 'stock' => 7],
                    ['weight_grams' => 250, 'price' => 540, 'stock' => 5],
                ],
            ],
        ];
    }
}
