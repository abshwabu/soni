<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subscriber;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    public function test_home_page_is_accessible_and_renders_inertia(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Home')
            ->has('featuredProducts')
            ->has('categories')
            ->has('banners')
            ->has('testimonials')
            ->has('settings')
            ->has('settings.store_name')
            ->has('cartCount')
            ->has('categoriesNav')
        );
    }

    public function test_customer_auth_routes_do_not_exist(): void
    {
        $this->get('/login')->assertStatus(404);
        $this->get('/register')->assertStatus(404);
        $this->get('/forgot-password')->assertStatus(404);
        $this->get('/reset-password')->assertStatus(404);
        $this->get('/dashboard')->assertStatus(404);
        $this->get('/profile')->assertStatus(404);
    }

    public function test_subscribers_endpoint_stores_subscriber_from_newsletter(): void
    {
        $testEmail = 'storefront_test_' . time() . '@example.com';

        $response = $this->post('/subscribers', [
            'email_or_phone' => $testEmail,
            'source' => 'footer_newsletter',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('subscribers', [
            'email_or_phone' => $testEmail,
            'source' => 'footer_newsletter',
        ]);
    }

    public function test_cart_endpoints_add_update_remove_items(): void
    {
        $variant = ProductVariant::where('is_active', true)->where('stock', '>', 0)->first();
        $this->assertNotNull($variant);

        // 1. Add to cart
        $addResponse = $this->post('/cart', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $addResponse->assertSessionHas('cart');
        $addResponse->assertSessionHas('success');

        // Check cart page
        $cartPage = $this->get('/cart');
        $cartPage->assertStatus(200);
        $cartPage->assertInertia(fn ($page) => $page
            ->component('Cart')
            ->where('cartCount', 1)
        );

        // 2. Update quantity
        $updateResponse = $this->patch("/cart/{$variant->id}", [
            'quantity' => 2,
        ]);
        $updateResponse->assertSessionHas('success');

        // 3. Remove from cart
        $deleteResponse = $this->delete("/cart/{$variant->id}");
        $deleteResponse->assertSessionHas('success');

        $emptyCartPage = $this->get('/cart');
        $emptyCartPage->assertInertia(fn ($page) => $page
            ->where('cartCount', 0)
        );
    }
}
