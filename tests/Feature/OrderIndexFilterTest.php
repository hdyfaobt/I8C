<?php

use App\Models\Order;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('status filter narrows the orders list', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $sent = Order::factory()->create(['status' => 'sent']);
    Order::factory()->create(['status' => 'ready_for_pickup']);

    $response = $this->actingAs($user)->get(route('orders.index', ['status' => 'sent']));

    $response->assertOk();
    $response->assertViewHas('hasActiveFilter', true);
    $response->assertViewHas('orders', fn ($orders) => $orders->count() === 1 && $orders->first()->id === $sent->id);
});

test('a comma-separated status filter matches any of the listed statuses', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    Order::factory()->create(['status' => 'awaiting_review']);
    Order::factory()->create(['status' => 'failed']);
    Order::factory()->create(['status' => 'sent']);

    $response = $this->actingAs($user)->get(route('orders.index', ['status' => 'awaiting_review,failed']));

    $response->assertViewHas('orders', fn ($orders) => $orders->count() === 2);
});

test('the mine filter only shows orders prepared by the current user', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');
    $mine = Order::factory()->create(['status' => 'sent', 'prepared_by' => $user->id]);
    Order::factory()->create(['status' => 'sent', 'prepared_by' => null]);

    $response = $this->actingAs($user)->get(route('orders.index', ['status' => 'sent', 'mine' => 1]));

    $response->assertViewHas('orders', fn ($orders) => $orders->count() === 1 && $orders->first()->id === $mine->id);
});

test('an orderpicker filter cannot leak statuses hidden from that role', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');
    Order::factory()->create(['status' => 'awaiting_review']);

    // Orderpicker isn't allowed to see 'awaiting_review' at all — the role
    // restriction in index() still applies underneath the extra filter.
    $response = $this->actingAs($user)->get(route('orders.index', ['status' => 'awaiting_review']));

    $response->assertViewHas('orders', fn ($orders) => $orders->isEmpty());
});

test('without a filter the list is unchanged', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    Order::factory()->count(2)->create(['status' => 'sent']);

    $response = $this->actingAs($user)->get(route('orders.index'));

    $response->assertViewHas('hasActiveFilter', false);
});
