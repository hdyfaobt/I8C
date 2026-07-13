<?php

use App\Console\Commands\ConsumeOrders;
use App\Models\Customer;
use App\Models\Order;
use App\Services\SalesforceService;

// ---------------------------------------------------------------------------
// ConsumeOrders — tests for the processOrder() logic
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->mock(SalesforceService::class, function ($mock) {
        $mock->shouldReceive('syncOrder')->andReturn('006XX0000000000AAA');
    });
});

it('syncs an order to Salesforce and marks it as sent', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'pending',
    ]);

    $command = app(ConsumeOrders::class);

    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('processOrder');
    $method->setAccessible(true);

    // processOrder() only reads 'order_id' and 'customer' from this payload
    // (for logging) — it re-fetches the order, with its items, straight
    // from the database for the actual sync.
    $result = $method->invoke($command, [
        'order_id' => $order->id,
        'customer_id' => $customer->id,
        'customer' => $customer->name,
        'total' => $order->totalPrice(),
        'notes' => $order->notes,
        'created_at' => now()->toISOString(),
    ]);

    expect($result)->toBeTrue();

    $order->refresh();
    expect($order->status)->toBe('sent');
});

it('returns false when order is not found in database', function () {
    $this->mock(SalesforceService::class, function ($mock) {
        $mock->shouldReceive('syncOrder')->never();
    });

    $command = app(ConsumeOrders::class);

    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('processOrder');
    $method->setAccessible(true);

    $result = $method->invoke($command, [
        'order_id' => 99999,
        'product' => 'Ghost',
        'total' => 100,
        'notes' => null,
    ]);

    expect($result)->toBeFalse();
});
