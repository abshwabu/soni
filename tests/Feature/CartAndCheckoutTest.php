<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CartAndCheckoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('place-order:127.0.0.1');
    }

    public function test_cart_service_adds_and_validates_stock(): void
    {
        $variant = ProductVariant::where('is_active', true)->where('stock', '>=', 2)->first();
        $this->assertNotNull($variant);

        $cartService = app(CartService::class);
        $cartService->clear();

        // 1. Add valid quantity
        $cartService->add($variant->id, 1);
        $this->assertEquals(1, $cartService->count());

        // 2. Subtotal and delivery fee
        $this->assertEquals($variant->price, $cartService->subtotal());
        $expectedFee = $variant->price >= $cartService->freeDeliveryThreshold() ? 0 : $cartService->deliveryFeeAddis();
        $this->assertEquals($expectedFee, $cartService->deliveryFee('delivery'));
        $this->assertEquals(0, $cartService->deliveryFee('pickup'));

        // 3. Update quantity
        $cartService->update($variant->id, 2);
        $this->assertEquals(2, $cartService->count());

        // 4. Remove item
        $cartService->remove($variant->id);
        $this->assertEquals(0, $cartService->count());
    }

    public function test_cart_page_renders_with_inertia_and_free_delivery_progress(): void
    {
        $variant = ProductVariant::where('is_active', true)->where('stock', '>', 0)->first();
        $this->assertNotNull($variant);

        $this->post('/cart', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertSessionHas('success');

        $response = $this->get('/cart');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cart')
            ->has('cart.items')
            ->has('cart.free_delivery')
            ->where('cart.count', 1)
        );
    }

    public function test_empty_cart_redirects_away_from_checkout(): void
    {
        $cartService = app(CartService::class);
        $cartService->clear();

        $response = $this->get('/checkout');
        $response->assertRedirect('/cart');
    }

    public function test_checkout_page_renders_when_cart_has_items(): void
    {
        $variant = ProductVariant::where('is_active', true)->where('stock', '>', 0)->first();
        $this->assertNotNull($variant);

        $cartService = app(CartService::class);
        $cartService->clear();
        $cartService->add($variant->id, 1);

        $response = $this->get('/checkout');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Checkout')
            ->has('cart.items')
            ->has('idempotencyToken')
        );
    }

    public function test_ethiopian_phone_validation_and_normalization(): void
    {
        Event::fake([OrderPlaced::class]);

        $variant = ProductVariant::where('is_active', true)->where('stock', '>=', 5)->first();
        $this->assertNotNull($variant);

        $cartService = app(CartService::class);
        $cartService->clear();
        $cartService->add($variant->id, 1);

        // 1. Invalid phone format
        $invalidResponse = $this->post('/checkout', [
            'customer_name' => 'Abebe Bikila',
            'phone' => '12345',
            'city' => 'Addis Ababa',
            'delivery_method' => 'delivery',
            'address' => 'Bole Medhanialem',
        ]);
        $invalidResponse->assertSessionHasErrors('phone');

        // 2. Valid Ethiopian format 09...
        $validResponse = $this->post('/checkout', [
            'customer_name' => 'Abebe Bikila',
            'phone' => '0911234567',
            'alt_phone' => '0711234567',
            'city' => 'Addis Ababa',
            'delivery_method' => 'delivery',
            'address' => 'Bole Medhanialem behind Edna Mall',
        ]);

        $validResponse->assertSessionHasNoErrors();
        $validResponse->assertRedirect();

        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals('+251911234567', $order->phone);
        $this->assertEquals('+251711234567', $order->alt_phone);
        $this->assertMatchesRegularExpression('/^SP-\d{8}-\d{4}$/', $order->order_number);
    }

    public function test_honeypot_blocks_spam_bots(): void
    {
        $variant = ProductVariant::where('is_active', true)->where('stock', '>', 0)->first();
        $this->assertNotNull($variant);

        $cartService = app(CartService::class);
        $cartService->clear();
        $cartService->add($variant->id, 1);

        $response = $this->post('/checkout', [
            'customer_name' => 'Spam Bot',
            'phone' => '0911234567',
            'city' => 'Addis Ababa',
            'delivery_method' => 'delivery',
            'address' => 'Bole',
            'website' => 'http://spam.com', // Honeypot filled
        ]);

        $response->assertSessionHasErrors('website');
    }

    public function test_place_order_decrements_stock_and_dispatches_event(): void
    {
        Event::fake([OrderPlaced::class]);

        $variant = ProductVariant::where('is_active', true)->where('stock', '>=', 5)->first();
        $this->assertNotNull($variant);
        $initialStock = $variant->stock;

        $cartService = app(CartService::class);
        $cartService->clear();
        $cartService->add($variant->id, 2);

        $response = $this->post('/checkout', [
            'customer_name' => 'Sara Haile',
            'phone' => '0922334455',
            'city' => 'Addis Ababa',
            'delivery_method' => 'delivery',
            'address' => 'Kazanchis Total, Addis Ababa',
            'notes' => 'Please deliver in morning',
        ]);

        $response->assertRedirect();
        $this->assertEquals(0, $cartService->count(), 'Cart should be cleared after order');

        // Check stock was decremented
        $variant->refresh();
        $this->assertEquals($initialStock - 2, $variant->stock);

        // Check Order and items
        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals('Sara Haile', $order->customer_name);
        $this->assertEquals('+251922334455', $order->phone);
        $this->assertCount(1, $order->items);

        $item = $order->items->first();
        $this->assertEquals($variant->id, $item->product_variant_id);
        $this->assertEquals(2, $item->quantity);
        $this->assertEquals($variant->price, $item->unit_price);
        $this->assertEquals($variant->price * 2, $item->line_total);

        // Check OrderPlaced event was dispatched
        Event::assertDispatched(OrderPlaced::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }

    public function test_thank_you_page_authorization(): void
    {
        $order = Order::first();
        $this->assertNotNull($order);

        // 1. Unauthorized session (no session match, no signed URL)
        session()->forget('placed_order_number');
        $unauthorized = $this->get("/order/{$order->order_number}/thank-you");
        $unauthorized->assertRedirect(route('home'));

        // 2. Authorized session
        session(['placed_order_number' => $order->order_number]);
        $authorized = $this->get("/order/{$order->order_number}/thank-you");
        $authorized->assertStatus(200);
        $authorized->assertInertia(fn ($page) => $page
            ->component('Order/ThankYou')
            ->where('order.order_number', $order->order_number)
        );
    }

    public function test_concurrency_race_condition_for_last_unit_produces_one_order_and_one_out_of_stock_error(): void
    {
        Event::fake([OrderPlaced::class]);

        // Create a unique test variant with exactly 1 unit in stock
        $product = Product::active()->first();
        $this->assertNotNull($product);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-SKU-' . time(),
            'size_ml' => 33,
            'price' => 7500,
            'stock' => 1,
            'is_active' => true,
        ]);

        $cartService = app(CartService::class);

        // Customer A: Adds the last unit to bag and places order
        $cartService->clear();
        $cartService->add($variant->id, 1);

        $orderA = $this->post('/checkout', [
            'customer_name' => 'Customer A',
            'phone' => '0911000001',
            'city' => 'Addis Ababa',
            'delivery_method' => 'delivery',
            'address' => 'Bole Medhanialem',
        ]);

        $orderA->assertSessionHasNoErrors();
        $orderA->assertRedirect();

        // Stock should now be 0
        $variant->refresh();
        $this->assertEquals(0, $variant->stock);

        // Customer B: Had the item in bag earlier, attempts to place order for the now-depleted unit
        $orderB = $this->withSession([
            'cart' => [
                $variant->id => [
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ])->post('/checkout', [
            'customer_name' => 'Customer B',
            'phone' => '0911000002',
            'city' => 'Addis Ababa',
            'delivery_method' => 'delivery',
            'address' => 'Old Airport',
        ]);

        // Customer B should receive a clean out of stock error
        $orderB->assertSessionHas('error');
        $this->assertStringContainsString('out of stock', session('error'));

        // Verify stock did not go negative
        $variant->refresh();
        $this->assertEquals(0, $variant->stock);

        // Exactly one order was created for this product
        $ordersCount = Order::whereHas('items', function ($q) use ($variant) {
            $q->where('product_variant_id', $variant->id);
        })->count();

        $this->assertEquals(1, $ordersCount, 'Only exactly one order should be created for the last unit');
    }
}
