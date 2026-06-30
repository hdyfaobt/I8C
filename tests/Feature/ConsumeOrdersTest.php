<?php

use App\Console\Commands\ConsumeOrders;
use App\Models\Customer;
use App\Models\Order;
use App\Services\SalesforceService;

// ---------------------------------------------------------------------------
// ConsumeOrders — tests for the processOrder() logic
// SalesforceService is mocked: no HTTP calls are made.
// ---------------------------------------------------------------------------

it('marks an order as sent and saves the salesforce id on success', function () {
    // Arrange: create a customer + order in the test database
    $customer = Customer::factory()->create();
    $order    = Order::factory()->create([
        'customer_id' => $customer->id,
        'status'      => 'pending',
    ]);

    // Mock SalesforceService to return a fake Opportunity ID
    $mock = Mockery::mock(SalesforceService::class);
    $mock->shouldReceive('createOpportunity')
        ->once()
        ->andReturn('006SF000001');

    app()->instance(SalesforceService::class, $mock);

    // Act: run the command's internal logic via Artisan
    $command = app(ConsumeOrders::class);

    $reflection = new ReflectionClass($command);
    $method     = $reflection->getMethod('processOrder');
    $method->setAccessible(true);

    $result = $method->invoke($command, [
        'order_id'    => $order->id,
        'customer_id' => $customer->id,
        'customer'    => $customer->name,
        'product'     => $order->product,
        'quantity'    => $order->quantity,
        'unit_price'  => $order->unit_price,
        'total'       => $order->quantity * $order->unit_price,
        'notes'       => $order->notes,
        'created_at'  => now()->toISOString(),
    ]);

    // Assert
    expect($result)->toBeTrue();

    $order->refresh();
    expect($order->status)->toBe('sent');
    expect($order->salesforce_id)->toBe('006SF000001');
});

it('returns false and does not crash when order is not found in database', function () {
    $command = app(ConsumeOrders::class);

    $reflection = new ReflectionClass($command);
    $method     = $reflection->getMethod('processOrder');
    $method->setAccessible(true);

    $result = $method->invoke($command, [
        'order_id' => 99999, // Non-existent ID
        'product'  => 'Ghost',
        'total'    => 100,
        'notes'    => null,
    ]);

    expect($result)->toBeFalse();
});

it('returns false when salesforce service throws an exception', function () {
    $customer = Customer::factory()->create();
    $order    = Order::factory()->create([
        'customer_id' => $customer->id,
        'status'      => 'pending',
    ]);

    // Mock SalesforceService to simulate a failure
    $mock = Mockery::mock(SalesforceService::class);
    $mock->shouldReceive('createOpportunity')
        ->once()
        ->andThrow(new \RuntimeException('Salesforce API error'));

    app()->instance(SalesforceService::class, $mock);

    $command = app(ConsumeOrders::class);

    $reflection = new ReflectionClass($command);
    $method     = $reflection->getMethod('processOrder');
    $method->setAccessible(true);

    $result = $method->invoke($command, [
        'order_id'    => $order->id,
        'customer_id' => $customer->id,
        'customer'    => $customer->name,
        'product'     => $order->product,
        'quantity'    => $order->quantity,
        'unit_price'  => $order->unit_price,
        'total'       => $order->quantity * $order->unit_price,
        'notes'       => $order->notes,
        'created_at'  => now()->toISOString(),
    ]);

    expect($result)->toBeFalse();

    // Order status should stay pending — not changed on failure
    $order->refresh();
    expect($order->status)->toBe('pending');
});
