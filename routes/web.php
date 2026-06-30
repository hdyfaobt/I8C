<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('userzone.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [App\Http\Controllers\Userzone\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [App\Http\Controllers\Userzone\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [App\Http\Controllers\Userzone\ProfileController::class, 'destroy'])->name('profile.destroy');

    // Customer CRUD routes — accessible to authenticated users only
    Route::resource('customers', App\Http\Controllers\Userzone\CustomerController::class);

    // Order routes — only index, create, store and show (no edit/delete for orders)
    Route::resource('orders', App\Http\Controllers\Userzone\OrderController::class)
        ->only(['index', 'create', 'store', 'show']);
});

require __DIR__.'/auth.php';
