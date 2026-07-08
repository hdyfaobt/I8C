<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

// ---------------------------------------------------------------------------
// Admin account management — only "admin" role can create/edit/delete accounts
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('admin can view the accounts list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk();
});

test('non-admin roles cannot access account management', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user)->get(route('admin.users.index'));

    $response->assertForbidden();
});

test('guests are redirected to login', function () {
    $response = $this->get(route('admin.users.index'));

    $response->assertRedirect(route('login'));
});

test('admin can create a new account with a role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Nieuwe Manager',
        'email' => 'manager@i8c.be',
        'password' => 'password123',
        'role' => 'manager',
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $newUser = User::where('email', 'manager@i8c.be')->first();

    expect($newUser)->not->toBeNull();
    expect($newUser->hasRole('manager'))->toBeTrue();
});

test('creating an account requires a valid role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Iemand',
        'email' => 'iemand@i8c.be',
        'password' => 'password123',
        'role' => 'not-a-real-role',
    ]);

    $response->assertSessionHasErrors('role');
});

test('admin can change an existing account role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $target = User::factory()->create();
    $target->assignRole('user');

    $response = $this->actingAs($admin)->put(route('admin.users.update', $target), [
        'name' => $target->name,
        'email' => $target->email,
        'role' => 'manager',
    ]);

    $response->assertRedirect(route('admin.users.index'));
    expect($target->fresh()->hasRole('manager'))->toBeTrue();
    expect($target->fresh()->hasRole('user'))->toBeFalse();
});

test('admin cannot delete their own account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

    $response->assertRedirect(route('admin.users.index'));
    expect(User::find($admin->id))->not->toBeNull();
});

test('admin can delete another account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $target = User::factory()->create();
    $target->assignRole('user');

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $target));

    $response->assertRedirect(route('admin.users.index'));
    expect(User::find($target->id))->toBeNull();
});
