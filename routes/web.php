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

// ── Startpagina ────────────────────────────────────────────────────────────
// No separate dashboard — the orders list IS the home page. Logged-in users
// land straight on their orders overview; guests go to the login page.
Route::get('/', fn () => Auth::check() ? redirect()->route('orders.index') : redirect()->route('login'));

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Order management — creating/cancelling/reordering/retrying an order and
// managing customers is a receptionist job (admin/manager can do everything
// too). Orderpicker is intentionally left out: they only view + pick.
//
// IMPORTANT: this block is registered BEFORE the generic "orders.show"
// route below (GET /orders/{order}). Laravel matches routes in the order
// they're registered, and a static path like /orders/create would
// otherwise be swallowed by the {order} wildcard first — Laravel would
// try to look up an order with id "create", fail, and return a 404.
// Registering the static "create" route first avoids that trap (same
// reasoning as the customers.search route above the {customer} wildcard).
Route::middleware(['auth', 'role:receptionist|admin|manager'])->group(function () {
    // Customer search — used by the async combobox in the order form (AJAX, JSON).
    // Registered before the resource route so "search" isn't captured by the {customer} wildcard.
    Route::get('/customers/search', [CustomerController::class, 'search'])
        ->name('customers.search');

    // A customer's past orders (AJAX/JSON) — powers the "previous orders"
    // panel on the order creation form. No route-order concerns here (unlike
    // customers.search above): this is a 3-segment URI, so it can never be
    // swallowed by the 2-segment customers.show wildcard below.
    Route::get('/customers/{customer}/orders', [CustomerController::class, 'orders'])
        ->name('customers.orders');

    Route::resource('customers', CustomerController::class);

    // Confirm a new customer to Salesforce with one click — no terminal
    // needed, same idea as the order sync consumer.
    Route::post('/customers/{customer}/sync-salesforce', [CustomerController::class, 'syncToSalesforce'])
        ->name('customers.syncSalesforce');

    Route::resource('orders', OrderController::class)->only(['create', 'store']);

    // Product catalog — viewing is a receptionist job too (looking up a
    // price/article number while placing an order). Managing the catalog
    // itself (add/edit/delete) is admin/manager only, see below.
    // "search" registered before the plain index route out of habit
    // (matches customers.search above) — no actual collision here since
    // there's no /products/{product} wildcard route.
    Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');

    // Order history for a single product — who ordered it, how often, and
    // when. Registered here (not in the admin|manager-only catalog
    // management group below) since viewing this is the same "receptionist
    // job" as viewing the catalog itself.
    Route::get('/products/{product}/orders', [ProductController::class, 'history'])
        ->name('products.history');

    // Recreate a previous order for the same customer (same products/qty/price)
    Route::post('/orders/{order}/repeat', [OrderController::class, 'repeat'])
        ->name('orders.repeat');

    // Manually retry a failed order — republishes it to RabbitMQ
    Route::post('/orders/{order}/retry', [OrderController::class, 'retry'])
        ->name('orders.retry');

    // Cancel an order that hasn't been synced to Salesforce yet
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])
        ->name('orders.cancel');

    // Confirm the customer actually received/collected their order —
    // the final step of the order lifecycle.
    Route::post('/orders/{order}/received', [OrderController::class, 'markReceived'])
        ->name('orders.received');

    // Toggle the "paid" flag — independent from the status lifecycle above,
    // see OrderController::togglePaid().
    Route::post('/orders/{order}/toggle-paid', [OrderController::class, 'togglePaid'])
        ->name('orders.togglePaid');

    // Printable invoice sheet — opened in a new tab.
    Route::get('/orders/{order}/print', [OrderController::class, 'print'])
        ->name('orders.print');

    // Refunds — every out-of-stock item owes the customer money back, see
    // RefundController. Same audience as the rest of this group: the
    // orderpicker reports the item out of stock, but actually processing
    // the refund is a receptionist/manager/admin job.
    Route::get('/refunds', [RefundController::class, 'index'])
        ->name('refunds.index');

    Route::post('/refunds/{item}/mark', [RefundController::class, 'markRefunded'])
        ->name('refunds.markRefunded');

    // Debts — the flip side of refunds: how much each customer still owes
    // us. See DebtController.
    Route::get('/debts', [DebtController::class, 'index'])
        ->name('debts.index');
});

Route::middleware('auth')->group(function () {
    // Orders — viewing is open to every role (admin, manager, receptionist,
    // orderpicker all need to be able to look at an order). Registered
    // AFTER orders.create above, see the note there.
    Route::resource('orders', OrderController::class)->only(['index', 'show']);
});

// Order review — accept/refuse a newly placed order. Restricted to
// receptionist/admin/manager. Orderpicker is deliberately left out: their
// job only starts once an order has already reached Salesforce (status
// 'sent') — see OrderController::index()/show() for the visibility filter.
Route::middleware(['auth', 'role:receptionist|admin|manager'])->group(function () {
    Route::post('/orders/{order}/accept', [OrderController::class, 'accept'])
        ->name('orders.accept');

    Route::post('/orders/{order}/refuse', [OrderController::class, 'refuse'])
        ->name('orders.refuse');
});

// Order picking — physically preparing an order. Orderpicker's core job
// (admin/manager can do everything too).
Route::middleware(['auth', 'role:orderpicker|admin|manager'])->group(function () {
    Route::post('/orders/{order}/items/{item}/pick', [OrderController::class, 'pickItem'])
        ->name('orders.items.pick');

    // Report an item out of stock instead of picked — see
    // OrderController::markItemOutOfStock().
    Route::post('/orders/{order}/items/{item}/out-of-stock', [OrderController::class, 'markItemOutOfStock'])
        ->name('orders.items.outOfStock');

    Route::post('/orders/{order}/picking-comment', [OrderController::class, 'updatePickingComment'])
        ->name('orders.pickingComment');

    // Claim an order and jump to its detail page — see
    // OrderController::takeCharge().
    Route::post('/orders/{order}/take-charge', [OrderController::class, 'takeCharge'])
        ->name('orders.takeCharge');

    Route::post('/orders/{order}/ready', [OrderController::class, 'markReady'])
        ->name('orders.ready');
});

// Product catalog management — add/edit/delete a product. Admin/manager
// only, per the app's rule that admin and manager have full access to
// everything while other roles are scoped down.
Route::middleware(['auth', 'role:admin|manager'])->group(function () {
    Route::resource('products', ProductController::class)->except(['index', 'show']);
});

// Account management — admins only. Lets an admin create accounts for
// managers/receptionists/orderpickers and assign or change their role.
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('users', AdminUserController::class)
            ->except(['show']);
    });

require __DIR__.'/auth.php';
