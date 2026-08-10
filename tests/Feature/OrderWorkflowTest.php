<?php

use App\Models\Order;
use App\Models\User;
use App\Services\RabbitMQPublisher;
use App\Services\SalesforceService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->mock(RabbitMQPublisher::class, function ($mock) {
        $mock->shouldReceive('publishOrder')->andReturn(true);
    });
    $this->seed(RoleSeeder::class);
});

function mockSalesforceSync(bool $succeeds = true): void
{
    test()->mock(SalesforceService::class, function ($mock) use ($succeeds) {
        $mock->shouldReceive('syncOrder')->andReturn($succeeds ? '006XX0000000000AAA' : null);
    });
}

// Accept / refuse — receptionist/admin/manager, only while awaiting review

test('accepting an order syncs it and marks it sent', function () {
    mockSalesforceSync(succeeds: true);
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'awaiting_review']);

    $response = $this->actingAs($user)->post(route('orders.accept', $order));

    $response->assertRedirect();
    expect($order->fresh()->status)->toBe('sent');
});

test('accepting an order marks it failed when salesforce sync fails', function () {
    mockSalesforceSync(succeeds: false);
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'awaiting_review']);

    $this->actingAs($user)->post(route('orders.accept', $order));

    expect($order->fresh()->status)->toBe('failed');
});

test('an order without items cannot be accepted', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'awaiting_review']);
    $order->items()->delete();

    $this->actingAs($user)->post(route('orders.accept', $order));

    expect($order->fresh()->status)->toBe('awaiting_review');
});

test('an order that is not awaiting review cannot be accepted again', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'sent']);

    $this->actingAs($user)->post(route('orders.accept', $order));

    expect($order->fresh()->status)->toBe('sent');
});

test('refusing an order awaiting review marks it refused', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'awaiting_review']);

    $this->actingAs($user)->post(route('orders.refuse', $order));

    expect($order->fresh()->status)->toBe('refused');
    expect($order->fresh()->refused_at)->not->toBeNull();
});

test('orderpicker cannot accept or refuse orders', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');
    $order = Order::factory()->create(['status' => 'awaiting_review']);

    $this->actingAs($user)->post(route('orders.accept', $order))->assertForbidden();
    $this->actingAs($user)->post(route('orders.refuse', $order))->assertForbidden();
});

// Cancel — awaiting_review/pending only

test('an awaiting order can be cancelled', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'awaiting_review']);

    $this->actingAs($user)->post(route('orders.cancel', $order));

    expect($order->fresh()->status)->toBe('cancelled');
});

test('a sent order cannot be cancelled', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'sent']);

    $this->actingAs($user)->post(route('orders.cancel', $order));

    expect($order->fresh()->status)->toBe('sent');
});

// Retry — pending/failed only

test('retrying a failed order can succeed and mark it sent', function () {
    mockSalesforceSync(succeeds: true);
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'failed']);

    $this->actingAs($user)->post(route('orders.retry', $order));

    expect($order->fresh()->status)->toBe('sent');
});

test('retrying a sent order does nothing', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'sent']);

    $this->actingAs($user)->post(route('orders.retry', $order));

    expect($order->fresh()->status)->toBe('sent');
});

// Picking — orderpicker/admin/manager

test('picking an item toggles it and clears out-of-stock', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');
    $order = Order::factory()->create(['status' => 'sent']);
    $order->items()->delete();
    $item = $order->items()->create(['product' => 'Salami', 'quantity' => 1, 'unit_price' => 10, 'out_of_stock_at' => now()]);

    $this->actingAs($user)->post(route('orders.items.pick', [$order, $item]));

    $item->refresh();
    expect($item->picked_at)->not->toBeNull();
    expect($item->out_of_stock_at)->toBeNull();
});

test('marking an item out of stock clears picked and adds a picking comment', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');
    $order = Order::factory()->create(['status' => 'sent']);
    $order->items()->delete();
    $item = $order->items()->create(['product' => 'Salami', 'quantity' => 1, 'unit_price' => 10, 'picked_at' => now()]);

    $this->actingAs($user)->post(route('orders.items.outOfStock', [$order, $item]));

    $item->refresh();
    expect($item->out_of_stock_at)->not->toBeNull();
    expect($item->picked_at)->toBeNull();
    expect($order->fresh()->picking_comment)->toContain('Salami');
});

test('an item from another order cannot be picked through this order', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');
    $orderA = Order::factory()->create(['status' => 'sent']);
    $orderB = Order::factory()->create(['status' => 'sent']);

    $response = $this->actingAs($user)->post(route('orders.items.pick', [$orderA, $orderB->items->first()]));

    $response->assertNotFound();
});

test('an item cannot be picked on an order not yet sent', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');
    $order = Order::factory()->create(['status' => 'awaiting_review']);

    $response = $this->actingAs($user)->post(route('orders.items.pick', [$order, $order->items->first()]));

    $response->assertForbidden();
});

test('an item cannot be marked out of stock on a cancelled order', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');
    $order = Order::factory()->create(['status' => 'cancelled']);

    $response = $this->actingAs($user)->post(route('orders.items.outOfStock', [$order, $order->items->first()]));

    $response->assertForbidden();
});

test('the picking comment cannot be updated on an order not yet sent', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');
    $order = Order::factory()->create(['status' => 'pending']);

    $response = $this->actingAs($user)->post(route('orders.pickingComment', $order), ['picking_comment' => 'test']);

    $response->assertForbidden();
});

test('receptionist cannot pick order items', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'sent']);

    $response = $this->actingAs($user)->post(route('orders.items.pick', [$order, $order->items->first()]));

    $response->assertForbidden();
});

// Take charge — claims a sent order for preparation

test('taking charge of a sent order records the preparer', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');
    $order = Order::factory()->create(['status' => 'sent']);

    $this->actingAs($user)->post(route('orders.takeCharge', $order));

    expect($order->fresh()->prepared_by)->toBe($user->id);
});

test('taking charge of a non-sent order does not assign a preparer', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');
    $order = Order::factory()->create(['status' => 'ready_for_pickup']);

    $this->actingAs($user)->post(route('orders.takeCharge', $order));

    expect($order->fresh()->prepared_by)->toBeNull();
});

// Marking ready — requires 'sent' status and every item resolved

test('an order can be marked ready once every item is resolved', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');
    $order = Order::factory()->create(['status' => 'sent']);
    $order->items()->delete();
    $order->items()->create(['product' => 'Salami', 'quantity' => 1, 'unit_price' => 10, 'picked_at' => now()]);

    $this->actingAs($user)->post(route('orders.ready', $order));

    expect($order->fresh()->status)->toBe('ready_for_pickup');
});

test('an order cannot be marked ready while items are unresolved', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');
    $order = Order::factory()->create(['status' => 'sent']);
    $order->items()->delete();
    $order->items()->create(['product' => 'Salami', 'quantity' => 1, 'unit_price' => 10]);

    $this->actingAs($user)->post(route('orders.ready', $order));

    expect($order->fresh()->status)->toBe('sent');
});

// Paid toggle — payment method required, undo restricted to admin/manager

test('marking an order paid requires a payment method', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'sent', 'paid' => false]);

    $response = $this->actingAs($user)->post(route('orders.togglePaid', $order), []);

    $response->assertSessionHasErrors('payment_method');
});

test('marking an order paid records the payment method', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'sent', 'paid' => false]);

    $this->actingAs($user)->post(route('orders.togglePaid', $order), ['payment_method' => 'cash']);

    $order->refresh();
    expect($order->paid)->toBeTrue();
    expect($order->payment_method)->toBe('cash');
});

test('receptionist cannot undo a payment', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'sent', 'paid' => true, 'payment_method' => 'cash']);

    $response = $this->actingAs($user)->post(route('orders.togglePaid', $order), []);

    $response->assertForbidden();
});

test('admin can undo a payment', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $order = Order::factory()->create(['status' => 'sent', 'paid' => true, 'payment_method' => 'cash']);

    $this->actingAs($admin)->post(route('orders.togglePaid', $order), []);

    expect($order->fresh()->paid)->toBeFalse();
});

test('a cancelled order cannot be marked paid', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'cancelled', 'paid' => false]);

    $this->actingAs($user)->post(route('orders.togglePaid', $order), ['payment_method' => 'cash']);

    expect($order->fresh()->paid)->toBeFalse();
});

// Received — final step, requires ready_for_pickup

test('marking an order received requires it to be ready for pickup', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'sent']);

    $this->actingAs($user)->post(route('orders.received', $order));

    expect($order->fresh()->status)->toBe('sent');
});

test('marking a ready order received records who handed it over', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = Order::factory()->create(['status' => 'ready_for_pickup']);

    $this->actingAs($user)->post(route('orders.received', $order));

    $order->refresh();
    expect($order->status)->toBe('received');
    expect($order->received_by)->toBe($user->id);
});
