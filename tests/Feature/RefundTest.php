<?php

use App\Models\Order;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function outOfStockItem(array $orderAttributes = [], array $itemAttributes = [])
{
    $order = Order::factory()->create($orderAttributes);
    $order->items()->delete();

    return $order->items()->create(array_merge([
        'product' => 'Salami XL',
        'quantity' => 2,
        'unit_price' => 15,
        'out_of_stock_at' => now(),
    ], $itemAttributes));
}

function refusedOrFailedOrder(array $orderAttributes = [])
{
    $order = Order::factory()->create($orderAttributes);
    $order->items()->delete();
    $order->items()->create(['product' => 'Salami XL', 'quantity' => 2, 'unit_price' => 15]);

    return $order;
}

test('refunds list splits pending and done items', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');

    $pending = outOfStockItem(['paid' => true]);
    $done = outOfStockItem(['paid' => true], ['refunded_at' => now()]);

    $response = $this->actingAs($user)->get(route('refunds.index'));

    $response->assertOk();
    $response->assertViewHas('pending', fn ($items) => $items->contains('id', $pending->id));
    $response->assertViewHas('done', fn ($items) => $items->contains('id', $done->id));
});

test('pending refund total only sums paid orders', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');

    outOfStockItem(['paid' => true], ['quantity' => 2, 'unit_price' => 15]);
    outOfStockItem(['paid' => false], ['quantity' => 3, 'unit_price' => 10]);

    $response = $this->actingAs($user)->get(route('refunds.index'));

    // Only the paid order's item (2 x 15) counts as an actual refund owed.
    $response->assertViewHas('pendingTotal', 30.0);
});

test('marking an item refunded toggles it and redirects back', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $item = outOfStockItem(['paid' => true]);

    $response = $this->actingAs($user)->post(route('refunds.markRefunded', $item));

    $response->assertRedirect();
    expect($item->fresh()->refunded_at)->not->toBeNull();
});

test('marking an already-refunded item resets it to pending', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $item = outOfStockItem(['paid' => true], ['refunded_at' => now()]);

    $this->actingAs($user)->post(route('refunds.markRefunded', $item));

    expect($item->fresh()->refunded_at)->toBeNull();
});

test('a paid refused order shows up as an open order-level refund', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = refusedOrFailedOrder(['paid' => true, 'status' => 'refused']);

    $response = $this->actingAs($user)->get(route('refunds.index'));

    $response->assertViewHas('pendingOrders', fn ($orders) => $orders->contains('id', $order->id));
    $response->assertViewHas('pendingTotal', 30.0);
});

test('a paid failed order shows up as an open order-level refund', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = refusedOrFailedOrder(['paid' => true, 'status' => 'failed']);

    $response = $this->actingAs($user)->get(route('refunds.index'));

    $response->assertViewHas('pendingOrders', fn ($orders) => $orders->contains('id', $order->id));
});

test('an unpaid refused order is not a refund candidate', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    refusedOrFailedOrder(['paid' => false, 'status' => 'refused']);

    $response = $this->actingAs($user)->get(route('refunds.index'));

    $response->assertViewHas('pendingOrders', fn ($orders) => $orders->isEmpty());
});

test('marking an order refunded toggles it and redirects back', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = refusedOrFailedOrder(['paid' => true, 'status' => 'refused']);

    $response = $this->actingAs($user)->post(route('refunds.markOrderRefunded', $order));

    $response->assertRedirect();
    expect($order->fresh()->refunded_at)->not->toBeNull();
});

test('marking an already-refunded order resets it to pending', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $order = refusedOrFailedOrder(['paid' => true, 'status' => 'refused', 'refunded_at' => now()]);

    $this->actingAs($user)->post(route('refunds.markOrderRefunded', $order));

    expect($order->fresh()->refunded_at)->toBeNull();
});

test('orderpicker cannot access refunds', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');

    $response = $this->actingAs($user)->get(route('refunds.index'));

    $response->assertForbidden();
});

test('guests are redirected to login', function () {
    $response = $this->get(route('refunds.index'));

    $response->assertRedirect(route('login'));
});
