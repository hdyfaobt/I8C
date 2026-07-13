<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Userzone\CustomerController;
use App\Http\Controllers\Userzone\DebtController;
use App\Http\Controllers\Userzone\OrderController;
use App\Http\Controllers\Userzone\ProductController;
use App\Http\Controllers\Userzone\ProfileController;
use App\Http\Controllers\Userzone\RefundController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Home
Route::get('/', fn () => Auth::check() ? redirect()->route('orders.index') : redirect()->route('login'));

// Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Customers, product catalog (view), order creation, refunds and debts — receptionist/admin/manager
Route::middleware(['auth', 'role:receptionist|admin|manager'])->group(function () {
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::get('/customers/{customer}/orders', [CustomerController::class, 'orders'])->name('customers.orders');
    Route::post('/customers/{customer}/sync-salesforce', [CustomerController::class, 'syncToSalesforce'])->name('customers.syncSalesforce');
    Route::resource('customers', CustomerController::class)->except(['destroy']);

    Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}/orders', [ProductController::class, 'history'])->name('products.history');

    Route::resource('orders', OrderController::class)->only(['create', 'store']);
    Route::post('/orders/{order}/repeat', [OrderController::class, 'repeat'])->name('orders.repeat');
    Route::post('/orders/{order}/retry', [OrderController::class, 'retry'])->name('orders.retry');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/received', [OrderController::class, 'markReceived'])->name('orders.received');
    Route::post('/orders/{order}/toggle-paid', [OrderController::class, 'togglePaid'])->name('orders.togglePaid');
    Route::get('/orders/{order}/print', [OrderController::class, 'print'])->name('orders.print');

    Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
    Route::post('/refunds/{item}/mark', [RefundController::class, 'markRefunded'])->name('refunds.markRefunded');

    Route::get('/debts', [DebtController::class, 'index'])->name('debts.index');
});

// Orders — view, every role
Route::middleware('auth')->group(function () {
    Route::resource('orders', OrderController::class)->only(['index', 'show']);
});

// Order review (accept/refuse) — receptionist/admin/manager
Route::middleware(['auth', 'role:receptionist|admin|manager'])->group(function () {
    Route::post('/orders/{order}/accept', [OrderController::class, 'accept'])->name('orders.accept');
    Route::post('/orders/{order}/refuse', [OrderController::class, 'refuse'])->name('orders.refuse');
});

// Order picking — orderpicker/admin/manager
Route::middleware(['auth', 'role:orderpicker|admin|manager'])->group(function () {
    Route::post('/orders/{order}/items/{item}/pick', [OrderController::class, 'pickItem'])->name('orders.items.pick');
    Route::post('/orders/{order}/items/{item}/out-of-stock', [OrderController::class, 'markItemOutOfStock'])->name('orders.items.outOfStock');
    Route::post('/orders/{order}/picking-comment', [OrderController::class, 'updatePickingComment'])->name('orders.pickingComment');
    Route::post('/orders/{order}/take-charge', [OrderController::class, 'takeCharge'])->name('orders.takeCharge');
    Route::post('/orders/{order}/ready', [OrderController::class, 'markReady'])->name('orders.ready');
});

// Product catalog management — admin/manager
Route::middleware(['auth', 'role:admin|manager'])->group(function () {
    Route::resource('products', ProductController::class)->except(['index', 'show']);
});

// Permanent deletion — admin only
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
});

// Admin — user management
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('users', AdminUserController::class)->except(['show']);
    });

require __DIR__.'/auth.php';
