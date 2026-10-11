<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
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
Route::post('/subscribers', [SubscriberController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('subscribers.store');

// Shop & Catalog
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/catalog', [ShopController::class, 'index'])->name('catalog.index');
Route::get('/category/{slug}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/brand/{slug}', [ShopController::class, 'brand'])->name('shop.brand');
Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/product/{slug}', [ShopController::class, 'show'])->name('product.show');

// Search & Live Suggestions
Route::get('/search', [ShopController::class, 'index'])->name('search');
Route::get('/search/suggestions', [SearchController::class, 'suggestions'])
    ->middleware('throttle:60,1')
    ->name('search.suggestions');

// Cart
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{variant}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{variant}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

// Guest Checkout & Place Order
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('throttle:15,1')
    ->name('checkout.store');

// Order Confirmation (Thank-you page)
Route::get('/order/{order_number}/thank-you', [CheckoutController::class, 'thankYou'])->name('order.thank-you');

// Admin Invoice
Route::middleware(['web', 'auth'])->get('/admin/orders/{order}/invoice', function (Order $order) {
    $order->load(['items.product', 'items.productVariant']);
    return view('admin.orders.invoice', compact('order'));
})->name('admin.orders.invoice');

// Gift Guide
Route::get('/gifts', [\App\Http\Controllers\GiftGuideController::class, 'index'])->name('gifts.index');

// About & Content Pages
Route::get('/about', [\App\Http\Controllers\PageController::class, 'about'])->name('about');
Route::get('/contact', [\App\Http\Controllers\PageController::class, 'contact'])->name('contact');
Route::post('/contact', [\App\Http\Controllers\PageController::class, 'submitContact'])
    ->middleware('throttle:10,1')
    ->name('contact.submit');
Route::get('/delivery-returns', fn () => app(\App\Http\Controllers\PageController::class)->show('delivery-returns'))->name('delivery-returns');
Route::get('/privacy', fn () => app(\App\Http\Controllers\PageController::class)->show('privacy'))->name('privacy');
Route::get('/terms', fn () => app(\App\Http\Controllers\PageController::class)->show('terms'))->name('terms');
Route::get('/p/{slug}', [\App\Http\Controllers\PageController::class, 'show'])->name('pages.show');

// Dynamic Sitemap XML
Route::get('/sitemap.xml', function (\App\Services\SitemapService $sitemapService) {
    return response($sitemapService->generateXml(), 200, [
        'Content-Type' => 'application/xml; charset=utf-8',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('sitemap');

// Telegram Bot Webhook
Route::post('/telegram/webhook/{secret}', [\App\Http\Controllers\TelegramWebhookController::class, 'handle'])->name('telegram.webhook');


