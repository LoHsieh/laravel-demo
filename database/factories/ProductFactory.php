<?php

namespace Database\Factories;

use App\Catalog\Roast;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => '測試咖啡豆 '.fake()->unique()->numberBetween(1, 99999),
            'origin' => fake()->randomElement(['衣索比亞', '肯亞', '哥倫比亞']),
            'process' => fake()->randomElement(['水洗', '日曬', '蜜處理']),
            'roast' => fake()->randomElement(Roast::cases()),
            'flavor_description' => '茉莉・檸檬・紅茶',
        ];
    }
}
