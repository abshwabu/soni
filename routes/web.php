<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SubscriberController;
use App\Models\Order;
use Illuminate\Support\Facades\Route;

// Home & First-Visit Promo
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/claim-welcome-offer', [HomeController::class, 'claimWelcomeOffer'])->name('welcome-offer.claim');

// Newsletter subscription
Route::post('/subscribers', [SubscriberController::class, 'store'])->name('subscribers.store');

// Shop & Catalog
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/category/{slug}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/brand/{slug}', [ShopController::class, 'brand'])->name('shop.brand');
Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/product/{slug}', [ShopController::class, 'show'])->name('product.show');

// Search & Live Suggestions
Route::get('/search', [ShopController::class, 'index'])->name('search');
Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');

// Cart
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{variant}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{variant}', [CartController::class, 'destroy'])->name('cart.destroy');

// Admin Invoice
Route::middleware(['web', 'auth'])->get('/admin/orders/{order}/invoice', function (Order $order) {
    $order->load(['items.product', 'items.productVariant']);
    return view('admin.orders.invoice', compact('order'));
})->name('admin.orders.invoice');
