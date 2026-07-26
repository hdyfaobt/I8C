<?php

use App\Models\User;

test('guests hitting the home page are redirected to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

test('logged in users hitting the home page are redirected to orders', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('orders.index'));
});
