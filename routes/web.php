<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Userzone\CustomerController;
use App\Http\Controllers\Userzone\OrderController;
use App\Http\Controllers\Userzone\ProfileController;
use Illuminate\Support\Facades\Route;

// ── Startpagina ────────────────────────────────────────────────────────────
// Iedereen wordt doorgestuurd naar de loginpagina
Route::get('/', fn () => redirect()->route('login'));

Route::get('/dashboard', function () {
    return view('userzone.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Customer search — used by the async combobox in the order form (AJAX, JSON).
    // Registered before the resource route so "search" isn't captured by the {customer} wildcard.
    Route::get('/customers/search', [CustomerController::class, 'search'])
        ->name('customers.search');

    // Customer CRUD routes — accessible to authenticated users only
    Route::resource('customers', CustomerController::class);

    // Order routes — only index, create, store and show (no edit/delete for orders)
    Route::resource('orders', OrderController::class)
        ->only(['index', 'create', 'store', 'show']);
});

// Account management — admins only. Lets an admin create accounts for
// managers/users and assign or change their role.
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('users', AdminUserController::class)
            ->except(['show']);
    });

require __DIR__.'/auth.php';
