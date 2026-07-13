<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'notes' => fake()->optional()->sentence(),
            'status' => 'pending',
        ];
    }

    /**
     * Every real order needs at least one product line. Attach one
     * automatically after creating, so existing `Order::factory()->create()`
     * calls keep working without every test having to think about items
     * explicitly. Tests that care about specific item data can still
     * create their own via `$order->items()->create([...])`.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Order $order) {
            if ($order->items()->count() === 0) {
                OrderItem::factory()->for($order)->create();
            }
        });
    }
}
