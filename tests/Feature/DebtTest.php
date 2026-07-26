<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('unpaid order shows up as a debt', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id, 'paid' => false]);
    $order->items()->delete();
    $order->items()->create(['product' => 'Salami', 'quantity' => 2, 'unit_price' => 10]);

    $response = $this->actingAs($user)->get(route('debts.index'));

    $response->assertOk();
    $response->assertViewHas('debts', fn ($debts) => $debts->contains(fn ($row) => $row['customer']->id === $customer->id && $row['total'] === 20.0));
});

test('paid orders are excluded from debts', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id, 'paid' => true]);
    $order->items()->delete();
    $order->items()->create(['product' => 'Salami', 'quantity' => 2, 'unit_price' => 10]);

    $response = $this->actingAs($user)->get(route('debts.index'));

    $response->assertViewHas('debts', fn ($debts) => $debts->doesntContain(fn ($row) => $row['customer']->id === $customer->id));
});

test('cancelled and refused orders are excluded from debts', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id, 'paid' => false, 'status' => 'cancelled']);
    $order->items()->delete();
    $order->items()->create(['product' => 'Salami', 'quantity' => 2, 'unit_price' => 10]);

    $response = $this->actingAs($user)->get(route('debts.index'));

    $response->assertViewHas('debts', fn ($debts) => $debts->isEmpty());
});

test('out-of-stock items lower the debt without a separate refund', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id, 'paid' => false]);
    $order->items()->delete();
    $order->items()->create(['product' => 'Salami', 'quantity' => 2, 'unit_price' => 10]);
    $order->items()->create(['product' => 'Kaas', 'quantity' => 1, 'unit_price' => 5, 'out_of_stock_at' => now()]);

    $response = $this->actingAs($user)->get(route('debts.index'));

    // Only the still-deliverable line (2 x 10) counts towards the debt.
    $response->assertViewHas('debts', fn ($debts) => $debts->first()['total'] === 20.0);
});

test('grand total sums debts across every customer', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');

    foreach ([10, 25] as $amount) {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'paid' => false]);
        $order->items()->delete();
        $order->items()->create(['product' => 'Item', 'quantity' => 1, 'unit_price' => $amount]);
    }

    $response = $this->actingAs($user)->get(route('debts.index'));

    $response->assertViewHas('grandTotal', 35.0);
});

test('orderpicker cannot access debts', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');

    $response = $this->actingAs($user)->get(route('debts.index'));

    $response->assertForbidden();
});

test('guests are redirected to login', function () {
    $response = $this->get(route('debts.index'));

    $response->assertRedirect(route('login'));
});
