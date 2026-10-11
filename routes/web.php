<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $featuredProducts = \App\Models\Product::with(['brand', 'variants', 'media'])
        ->active()
        ->featured()
        ->latest('id')
        ->limit(8)
        ->get();

    // If no featured products, get active products
    if ($featuredProducts->isEmpty()) {
        $featuredProducts = \App\Models\Product::with(['brand', 'variants', 'media'])
            ->active()
            ->latest('id')
            ->limit(8)
            ->get();
    }

    $banners = \App\Models\Banner::active()->ordered()->get();
    $categories = \App\Models\Category::orderBy('sort_order')->get();
    $testimonials = \App\Models\Testimonial::active()->latest('id')->limit(4)->get();

    return Inertia::render('Home', [
        'featuredProducts' => $featuredProducts,
        'banners' => $banners,
        'categories' => $categories,
        'testimonials' => $testimonials,
    ]);
})->name('home');

Route::post('/subscribers', [\App\Http\Controllers\SubscriberController::class, 'store'])->name('subscribers.store');

Route::get('/cart', [\App\Http\Controllers\CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [\App\Http\Controllers\CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{variant}', [\App\Http\Controllers\CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{variant}', [\App\Http\Controllers\CartController::class, 'destroy'])->name('cart.destroy');

Route::middleware(['web', 'auth'])->get('/admin/orders/{order}/invoice', function (\App\Models\Order $order) {
    $order->load(['items.product', 'items.productVariant']);
    return view('admin.orders.invoice', compact('order'));
})->name('admin.orders.invoice');


