<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::post('/claim-welcome-offer', [\App\Http\Controllers\HomeController::class, 'claimWelcomeOffer'])->name('welcome-offer.claim');

Route::post('/subscribers', [\App\Http\Controllers\SubscriberController::class, 'store'])->name('subscribers.store');

Route::get('/cart', [\App\Http\Controllers\CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [\App\Http\Controllers\CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{variant}', [\App\Http\Controllers\CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{variant}', [\App\Http\Controllers\CartController::class, 'destroy'])->name('cart.destroy');

Route::middleware(['web', 'auth'])->get('/admin/orders/{order}/invoice', function (\App\Models\Order $order) {
    $order->load(['items.product', 'items.productVariant']);
    return view('admin.orders.invoice', compact('order'));
})->name('admin.orders.invoice');


