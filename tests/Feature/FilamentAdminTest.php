<?php

namespace Tests\Feature;

use App\Enums\Concentration;
use App\Enums\Gender;
use App\Enums\OrderStatus;
use App\Enums\ScentFamily;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentAdminTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::first() ?? User::factory()->create([
            'email' => 'admin@soniperfume.com',
        ]);

        Product::where('slug', 'like', 'royal-amber-gold%')->forceDelete();
    }

    protected function tearDown(): void
    {
        Product::where('slug', 'like', 'royal-amber-gold%')->forceDelete();
        parent::tearDown();
    }

    public function test_admin_can_access_dashboard_with_widgets(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertSuccessful();
        $response->assertSee('Soni Perfume');
    }

    public function test_admin_can_access_all_resource_list_pages(): void
    {
        $routes = [
            '/admin/products',
            '/admin/products/create',
            '/admin/brands',
            '/admin/categories',
            '/admin/orders',
            '/admin/banners',
            '/admin/testimonials',
            '/admin/subscribers',
            '/admin/manage-settings',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->admin)->get($route);
            $response->assertSuccessful();
        }
    }

    public function test_admin_can_create_product_with_three_sizes(): void
    {
        $brand = Brand::first();
        $category = Category::first();

        Livewire::actingAs($this->admin)
            ->test(CreateProduct::class)
            ->fillForm([
                'name' => 'Royal Amber Gold',
                'slug' => 'royal-amber-gold',
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'gender' => Gender::UNISEX->value,
                'concentration' => Concentration::EDP->value,
                'scent_family' => ScentFamily::ORIENTAL->value,
                'short_description' => 'A royal scent with deep amber and vanilla.',
                'description' => '<p>Exquisite craftsmanship in every drop.</p>',
                'top_notes' => 'Bergamot, Saffron',
                'heart_notes' => 'Amber, Rose',
                'base_notes' => 'Vanilla, Oud',
                'is_active' => true,
                'is_featured' => true,
                'variants' => [
                    [
                        'size_ml' => 50,
                        'price' => 4500,
                        'compare_at_price' => 5000,
                        'sku' => 'RAG-50',
                        'stock' => 15,
                        'is_active' => true,
                    ],
                    [
                        'size_ml' => 100,
                        'price' => 7500,
                        'compare_at_price' => 8500,
                        'sku' => 'RAG-100',
                        'stock' => 25,
                        'is_active' => true,
                    ],
                    [
                        'size_ml' => 200,
                        'price' => 12000,
                        'compare_at_price' => null,
                        'sku' => 'RAG-200',
                        'stock' => 5,
                        'is_active' => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'royal-amber-gold')->first();
        $this->assertNotNull($product);
        $this->assertEquals('Royal Amber Gold', $product->name);
        $this->assertEquals(3, $product->variants()->count());
        $this->assertEquals(4500, $product->price_from);
        $this->assertEquals(45, $product->variants()->sum('stock'));
    }

    public function test_admin_can_view_order_and_change_order_status(): void
    {
        $order = Order::first();
        $this->assertNotNull($order);

        $response = $this->actingAs($this->admin)->get("/admin/orders/{$order->id}");
        $response->assertSuccessful();
        $response->assertSee($order->order_number);

        Livewire::actingAs($this->admin)
            ->test(ViewOrder::class, ['record' => $order->id])
            ->callAction('changeStatus', [
                'status' => OrderStatus::OUT_FOR_DELIVERY->value,
            ]);

        $order->refresh();
        $this->assertEquals(OrderStatus::OUT_FOR_DELIVERY, $order->status);
    }

    public function test_admin_can_update_store_settings(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'store_name' => 'Soni Perfume Boutique',
                'delivery_fee_addis' => '300',
                'free_delivery_threshold' => '6000',
                'welcome_discount_text' => 'Complimentary luxury gift wrapping on all orders!',
                'store_whatsapp' => '+251 91 123 4567',
                'telegram_url' => '@soniperfume_concierge',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals('Soni Perfume Boutique', Setting::get('store_name'));
        $this->assertEquals('300', Setting::get('delivery_fee_addis'));
        $this->assertEquals('6000', Setting::get('free_delivery_threshold'));
        $this->assertEquals('+251 91 123 4567', Setting::get('store_whatsapp'));
        $this->assertEquals('@soniperfume_concierge', Setting::get('telegram_url'));
    }

    public function test_admin_can_view_printable_invoice(): void
    {
        $order = Order::first();
        $response = $this->actingAs($this->admin)->get("/admin/orders/{$order->id}/invoice");

        $response->assertSuccessful();
        $response->assertSee($order->order_number);
        $response->assertSee('Soni Perfume');
        $response->assertSee('Print Invoice');
    }

    public function test_admin_can_trigger_resend_telegram_action(): void
    {
        $order = Order::first();

        Livewire::actingAs($this->admin)
            ->test(ViewOrder::class, ['record' => $order->id])
            ->callAction('resendTelegram')
            ->assertHasNoActionErrors();
    }

    public function test_admin_can_export_subscribers_csv(): void
    {
        Subscriber::firstOrCreate([
            'email_or_phone' => 'selam@example.com',
            'source' => 'footer',
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Filament\Resources\SubscriberResource\Pages\ListSubscribers::class)
            ->callTableAction('exportCsv')
            ->assertFileDownloaded('subscribers-' . now()->format('Y-m-d') . '.csv');
    }
}
