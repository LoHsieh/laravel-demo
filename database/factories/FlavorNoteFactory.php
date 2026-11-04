<?php

namespace Database\Factories;

use App\Models\FlavorNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlavorNote>
 */
class FlavorNoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => '風味 '.fake()->unique()->numberBetween(1, 99999),
        ];
    }
}
