<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\RabbitMQPublisher;

// ---------------------------------------------------------------------------
// Order repeat — recreate a previous order for the same customer
// ---------------------------------------------------------------------------

beforeEach(function () {
    // Avoid real RabbitMQ connections during tests.
    $this->mock(RabbitMQPublisher::class, function ($mock) {
        $mock->shouldReceive('publishOrder')->andReturn(true);
    });
});

test('repeating an order creates a new order with the same details', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $original = Order::factory()->create([
        'customer_id' => $customer->id,
        'product' => 'Salami XL',
        'quantity' => 5,
        'unit_price' => 12.50,
        'notes' => 'Vaste klant',
        'status' => 'sent',
    ]);

    $response = $this->actingAs($user)->post(route('orders.repeat', $original));

    $response->assertRedirect();

    $newOrder = Order::where('id', '!=', $original->id)->latest()->first();

    expect($newOrder)->not->toBeNull();
    expect($newOrder->customer_id)->toBe($customer->id);
    expect($newOrder->product)->toBe('Salami XL');
    expect($newOrder->quantity)->toBe(5);
    expect((float) $newOrder->unit_price)->toBe(12.50);
    expect($newOrder->status)->toBe('pending');
});

test('repeating an order requires authentication', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);

    $response = $this->post(route('orders.repeat', $order));

    $response->assertRedirect(route('login'));
});
