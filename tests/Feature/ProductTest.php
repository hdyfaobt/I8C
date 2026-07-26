<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// Catalog viewing — receptionist/admin/manager

test('receptionist can view the product catalog', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    Product::factory()->create(['name' => 'Salami XL']);

    $response = $this->actingAs($user)->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('Salami XL');
});

test('orderpicker cannot view the product catalog', function () {
    $user = User::factory()->create();
    $user->assignRole('orderpicker');

    $response = $this->actingAs($user)->get(route('products.index'));

    $response->assertForbidden();
});

test('guests are redirected to login', function () {
    $response = $this->get(route('products.index'));

    $response->assertRedirect(route('login'));
});

test('product search matches name or article number', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $match = Product::factory()->create(['name' => 'Chèvre', 'article_number' => 'ART-001']);
    Product::factory()->create(['name' => 'Salami', 'article_number' => 'ART-002']);

    $response = $this->actingAs($user)->getJson(route('products.search', ['q' => 'ART-001']));

    $response->assertOk();
    $response->assertJsonCount(1);
    $response->assertJsonFragment(['id' => $match->id]);
});

test('product search limits results to 15', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    Product::factory()->count(20)->create(['name' => 'Repeated Product']);

    $response = $this->actingAs($user)->getJson(route('products.search', ['q' => 'Repeated']));

    $response->assertOk();
    $response->assertJsonCount(15);
});

// Catalog management — admin/manager only

test('admin can create a product', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('products.store'), [
        'article_number' => 'ART-100',
        'name' => 'Nieuw Product',
        'price' => 9.99,
    ]);

    $response->assertRedirect(route('products.index'));
    expect(Product::where('article_number', 'ART-100')->exists())->toBeTrue();
});

test('receptionist cannot create a product', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');

    $response = $this->actingAs($user)->post(route('products.store'), [
        'article_number' => 'ART-101',
        'name' => 'Geweigerd',
        'price' => 5,
    ]);

    $response->assertForbidden();
});

test('creating a product requires a unique article number', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Product::factory()->create(['article_number' => 'ART-200']);

    $response = $this->actingAs($admin)->post(route('products.store'), [
        'article_number' => 'ART-200',
        'name' => 'Dubbel',
        'price' => 5,
    ]);

    $response->assertSessionHasErrors('article_number');
});

test('manager can update a product', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $product = Product::factory()->create(['price' => 10]);

    $response = $this->actingAs($manager)->put(route('products.update', $product), [
        'article_number' => $product->article_number,
        'name' => $product->name,
        'price' => 15,
    ]);

    $response->assertRedirect(route('products.index'));
    expect((float) $product->fresh()->price)->toBe(15.0);
});

test('admin can delete a product', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)->delete(route('products.destroy', $product));

    $response->assertRedirect(route('products.index'));
    expect(Product::find($product->id))->toBeNull();
});

// Sales history per product

test('product history excludes out-of-stock items from revenue', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $product = Product::factory()->create(['price' => 20]);

    $order = Order::factory()->create(['status' => 'sent']);
    $order->items()->delete();
    $order->items()->create([
        'product_id' => $product->id,
        'product' => $product->name,
        'quantity' => 2,
        'unit_price' => 20,
    ]);

    $outOfStockOrder = Order::factory()->create(['status' => 'sent']);
    $outOfStockOrder->items()->delete();
    $outOfStockOrder->items()->create([
        'product_id' => $product->id,
        'product' => $product->name,
        'quantity' => 3,
        'unit_price' => 20,
        'out_of_stock_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('products.history', $product));

    $response->assertOk();
    $response->assertViewHas('totalQuantity', 2);
    $response->assertViewHas('totalRevenue', 40.0);
});

test('product history excludes cancelled and refused orders from revenue', function () {
    $user = User::factory()->create();
    $user->assignRole('receptionist');
    $product = Product::factory()->create(['price' => 10]);

    $cancelled = Order::factory()->create(['status' => 'cancelled']);
    $cancelled->items()->delete();
    $cancelled->items()->create([
        'product_id' => $product->id,
        'product' => $product->name,
        'quantity' => 5,
        'unit_price' => 10,
    ]);

    $response = $this->actingAs($user)->get(route('products.history', $product));

    $response->assertOk();
    $response->assertViewHas('totalRevenue', 0.0);
    // Still counted as a past order/quantity, just not as revenue.
    $response->assertViewHas('totalQuantity', 5);
});
