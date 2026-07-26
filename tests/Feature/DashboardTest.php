<?php

use App\Models\Order;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('guests are redirected to login', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('receptionist sees the operational overview', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    Order::factory()->create(['status' => 'awaiting_review']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertViewHas('isPureOrderpicker', false);
    $response->assertViewHas('needsAttention', 1);
});

test('a pure orderpicker sees the picking-focused overview', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');
    Order::factory()->create(['status' => 'sent']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertViewHas('isPureOrderpicker', true);
    $response->assertViewHas('toPick', 1);
});

test('an admin who also has orderpicker gets the full overview, not the picker one', function () {
    $admin = User::factory()->create();
    $admin->assignRole(['admin', 'orderpicker']);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertViewHas('isPureOrderpicker', false);
    $response->assertViewHas('usersCount');
});

test('manager also sees the users count, same as admin', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $response = $this->actingAs($manager)->get(route('dashboard'));

    $response->assertOk();
    $response->assertViewHas('usersCount');
});

test('debts and refunds totals reflect open amounts', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');

    $order = Order::factory()->create(['paid' => false]);
    $order->items()->delete();
    $order->items()->create(['product' => 'Salami', 'quantity' => 2, 'unit_price' => 10]);

    $paidOrder = Order::factory()->create(['paid' => true]);
    $paidOrder->items()->delete();
    $paidOrder->items()->create(['product' => 'Kaas', 'quantity' => 1, 'unit_price' => 5, 'out_of_stock_at' => now()]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertViewHas('debtorsTotal', 20.0);
    $response->assertViewHas('refundsTotal', 5.0);
});
