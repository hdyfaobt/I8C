<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;

test('customer show page is displayed with its details', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('customers.show', $customer));

    $response->assertOk();
    $response->assertSee($customer->name);
    $response->assertSee($customer->email);
});

test('customer show page lists the customer orders', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'product' => 'Testproduct XYZ',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('customers.show', $customer));

    $response->assertOk();
    $response->assertSee('Testproduct XYZ');
});

test('customer show page requires authentication', function () {
    $customer = Customer::factory()->create();

    $response = $this->get(route('customers.show', $customer));

    $response->assertRedirect(route('login'));
});

test('customer search returns matching customers as json', function () {
    $user = User::factory()->create();
    $match = Customer::factory()->create(['name' => 'Jan Peeters', 'company' => 'Acme BV']);
    Customer::factory()->create(['name' => 'Someone Else', 'company' => 'Other Corp']);

    $response = $this
        ->actingAs($user)
        ->getJson(route('customers.search', ['q' => 'Jan']));

    $response->assertOk();
    $response->assertJsonCount(1);
    $response->assertJsonFragment(['id' => $match->id, 'name' => 'Jan Peeters']);
});

test('customer search matches on company name too', function () {
    $user = User::factory()->create();
    $match = Customer::factory()->create(['name' => 'Foo Bar', 'company' => 'Acme BV']);

    $response = $this
        ->actingAs($user)
        ->getJson(route('customers.search', ['q' => 'Acme']));

    $response->assertOk();
    $response->assertJsonFragment(['id' => $match->id]);
});

test('customer search limits results to 15', function () {
    $user = User::factory()->create();
    Customer::factory()->count(20)->create(['name' => 'Repeated Name']);

    $response = $this
        ->actingAs($user)
        ->getJson(route('customers.search', ['q' => 'Repeated']));

    $response->assertOk();
    $response->assertJsonCount(15);
});

test('customer search requires authentication', function () {
    $response = $this->getJson(route('customers.search', ['q' => 'test']));

    $response->assertStatus(401);
});
