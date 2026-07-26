<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_number' => fake()->unique()->numerify('ART-#####'),
            'name' => fake()->words(2, true),
            'price' => fake()->randomFloat(2, 1, 500),
        ];
    }
}
