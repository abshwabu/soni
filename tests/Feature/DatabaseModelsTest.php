<?php

namespace Tests\Feature;

use App\Enums\Concentration;
use App\Enums\Gender;
use App\Enums\ScentFamily;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class DatabaseModelsTest extends TestCase
{
    public function test_seeded_data_exists_and_is_realistic(): void
    {
        $this->assertEquals(8, Brand::count());
        $this->assertEquals(6, Category::count());
        $this->assertGreaterThanOrEqual(30, Product::count());
        $this->assertGreaterThanOrEqual(60, ProductVariant::count());
        $this->assertEquals(4, \App\Models\Banner::count());
        $this->assertEquals(6, \App\Models\Testimonial::count());
        $this->assertEquals(13, Setting::count());
    }

    public function test_money_accessors_format_properly_in_etb(): void
    {
        $variant = ProductVariant::first();
        $this->assertNotNull($variant);
        $this->assertIsInt($variant->price);
        $this->assertStringStartsWith('ETB ', $variant->formatted_price);

        $product = Product::with('variants')->first();
        $this->assertNotNull($product);
        $this->assertIsInt($product->price_from);
        $this->assertStringStartsWith('ETB ', $product->formatted_price_from);
    }

    public function test_scopes_work_as_expected(): void
    {
        $this->assertNotEmpty(Product::active()->get());
        $this->assertNotEmpty(Product::featured()->get());
        $this->assertNotEmpty(Product::inStock()->get());
        $this->assertNotEmpty(Product::bestseller()->get());
    }

    public function test_foreign_key_violations_are_rejected(): void
    {
        $this->expectException(QueryException::class);

        Product::create([
            'brand_id' => 999999,
            'category_id' => 999999,
            'name' => 'Invalid FK Product',
            'slug' => 'invalid-fk-product',
            'gender' => Gender::UNISEX,
            'concentration' => Concentration::EDP,
            'scent_family' => ScentFamily::WOODY,
        ]);
    }

    public function test_order_creation_with_items_and_money_calculations(): void
    {
        $product = Product::first();
        $variant = $product->variants()->first();

        $order = Order::create([
            'customer_name' => 'Abebe Bikila',
            'phone' => '+251911223344',
            'city' => 'Addis Ababa',
            'address' => 'Bole Medhanialem',
            'delivery_method' => \App\Enums\DeliveryMethod::DELIVERY,
            'subtotal' => $variant->price * 2,
            'delivery_fee' => 250,
            'total' => ($variant->price * 2) + 250,
        ]);

        $this->assertNotNull($order->order_number);
        $this->assertStringStartsWith('SP-', $order->order_number);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_label' => $variant->label,
            'unit_price' => $variant->price,
            'quantity' => 2,
            'line_total' => $variant->price * 2,
        ]);

        $this->assertCount(1, $order->fresh()->items);
        $this->assertStringStartsWith('ETB ', $order->formatted_total);
        $this->assertStringStartsWith('ETB ', $item->formatted_line_total);
    }
}
