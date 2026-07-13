<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product' => fake()->words(3, true),
            'quantity' => fake()->numberBetween(1, 50),
            'unit_price' => fake()->randomFloat(2, 10, 5000),
            'picked_at' => null,
        ];
    }
}
