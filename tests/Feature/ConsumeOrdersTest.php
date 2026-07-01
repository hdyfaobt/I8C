<?php

use App\Console\Commands\ConsumeOrders;
use App\Models\Customer;
use App\Models\Order;

// ---------------------------------------------------------------------------
// ConsumeOrders — tests for the processOrder() logic
// ---------------------------------------------------------------------------

it('marks an order as sent on success', function () {
    $customer = Customer::factory()->create();
    $order    = Order::factory()->create([
        'customer_id' => $customer->id,
        'status'      => 'pending',
    ]);

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

    expect($result)->toBeTrue();

    $order->refresh();
    expect($order->status)->toBe('sent');
});

it('returns false when order is not found in database', function () {
    $command = app(ConsumeOrders::class);

    $reflection = new ReflectionClass($command);
    $method     = $reflection->getMethod('processOrder');
    $method->setAccessible(true);

    $result = $method->invoke($command, [
        'order_id' => 99999,
        'product'  => 'Ghost',
        'total'    => 100,
        'notes'    => null,
    ]);

    expect($result)->toBeFalse();
});
