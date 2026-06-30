<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'product'     => fake()->words(3, true),
            'quantity'    => fake()->numberBetween(1, 50),
            'unit_price'  => fake()->randomFloat(2, 10, 5000),
            'notes'       => fake()->optional()->sentence(),
            'status'      => 'pending',
        ];
    }
}
