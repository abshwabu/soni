<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::middleware(['web', 'auth'])->get('/admin/orders/{order}/invoice', function (\App\Models\Order $order) {
    $order->load(['items.product', 'items.productVariant']);
    return view('admin.orders.invoice', compact('order'));
})->name('admin.orders.invoice');

