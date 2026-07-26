<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\RabbitMQPublisher;
use App\Services\SalesforceService;
use Database\Seeders\RoleSeeder;

// Order repeat — recreate a previous order for the same customer

beforeEach(function () {
    // No real RabbitMQ/Salesforce calls in tests.
    $this->mock(RabbitMQPublisher::class, function ($mock) {
        $mock->shouldReceive('publishOrder')->andReturn(true);
    });
    $this->mock(SalesforceService::class, function ($mock) {
        $mock->shouldReceive('syncOrder')->andReturn('006XX0000000000AAA');
    });

    $this->seed(RoleSeeder::class);
});

test('repeating an order creates a new order with the same details', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');

    $customer = Customer::factory()->create();
    $original = Order::factory()->create([
        'customer_id' => $customer->id,
        'notes' => 'Vaste klant',
        'status' => 'sent',
    ]);
    // Replace the factory's random default item with a known one.
    $original->items()->delete();
    $original->items()->create([
        'product' => 'Salami XL',
        'quantity' => 5,
        'unit_price' => 12.50,
    ]);

    $response = $this->actingAs($user)->post(route('orders.repeat', $original));

    $response->assertRedirect();

    $newOrder = Order::where('id', '!=', $original->id)->latest()->first();

    expect($newOrder)->not->toBeNull();
    expect($newOrder->customer_id)->toBe($customer->id);
    expect($newOrder->items)->toHaveCount(1);
    expect($newOrder->items->first()->product)->toBe('Salami XL');
    expect($newOrder->items->first()->quantity)->toBe(5);
    expect((float) $newOrder->items->first()->unit_price)->toBe(12.50);
    // No manual review step — synced straight through, see OrderController::syncOrderNow().
    expect($newOrder->status)->toBe('sent');
});

test('repeating an order requires authentication', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);

    $response = $this->post(route('orders.repeat', $order));

    $response->assertRedirect(route('login'));
});

test('repeating an order requires the receptionist, admin or manager role', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');

    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);

    $response = $this->actingAs($user)->post(route('orders.repeat', $order));

    $response->assertForbidden();
});
