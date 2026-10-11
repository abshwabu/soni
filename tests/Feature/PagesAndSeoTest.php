<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PagesAndSeoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.telegram.bot_token', '123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ');
        Config::set('services.telegram.admin_chat_id', '987654321');
    }

    public function test_about_page_renders_successfully(): void
    {
        $response = $this->get('/about');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('About')
                ->has('page')
                ->has('storyImages')
                ->has('seo')
            );
    }

    public function test_contact_page_renders_with_store_details(): void
    {
        $response = $this->get('/contact');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Contact')
                ->has('storeInfo')
                ->has('seo')
            );
    }

    public function test_contact_form_submission_validates_and_forwards_to_telegram(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->post('/contact', [
            'name' => 'Almaz Ayana',
            'phone' => '+251911223344',
            'email' => 'almaz@example.com',
            'subject' => 'Inquiry regarding Baccarat Rouge',
            'message' => 'Hello Soni Perfume, do you have Baccarat Rouge 540 Extrait in stock today?',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Http::assertSent(function ($request) {
            return str_contains($request['text'], 'New Contact Form Inquiry')
                && str_contains($request['text'], 'Almaz Ayana')
                && str_contains($request['text'], '+251911223344')
                && str_contains($request['text'], 'Baccarat Rouge');
        });
    }

    public function test_contact_form_requires_mandatory_fields(): void
    {
        $response = $this->post('/contact', [
            'name' => '',
            'phone' => '',
            'message' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'phone', 'message']);
    }

    public function test_cms_policy_pages_render_successfully(): void
    {
        foreach (['delivery-returns', 'privacy', 'terms'] as $slug) {
            $response = $this->get("/{$slug}");
            $response->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('Page')
                    ->where('page.slug', $slug)
                    ->has('seo')
                );
        }
    }

    public function test_gift_guide_page_renders_curated_sections(): void
    {
        $response = $this->get('/gifts');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Gifts')
                ->has('forHim')
                ->has('forHer')
                ->has('under3000')
                ->has('luxury')
                ->has('seo')
            );
    }

    public function test_sitemap_xml_endpoint_serves_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');

        $content = $response->getContent();
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('<urlset', $content);
        $this->assertStringContainsString('/shop', $content);
        $this->assertStringContainsString('/gifts', $content);
        $this->assertStringContainsString('/about', $content);
        $this->assertStringContainsString('/contact', $content);
        $this->assertStringContainsString('/delivery-returns', $content);
    }

    public function test_sitemap_generate_artisan_command_writes_file(): void
    {
        $this->artisan('sitemap:generate')
            ->expectsOutputToContain('Sitemap successfully generated')
            ->assertSuccessful();

        $this->assertFileExists(public_path('sitemap.xml'));
        $content = file_get_contents(public_path('sitemap.xml'));
        $this->assertStringContainsString('<urlset', $content);
    }

    public function test_robots_txt_exists_and_disallows_admin(): void
    {
        $this->assertFileExists(public_path('robots.txt'));
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Disallow: /checkout', $robots);
        $this->assertStringContainsString('Sitemap:', $robots);
    }

    public function test_product_slug_change_creates_301_redirect(): void
    {
        $product = Product::active()->first();
        $this->assertNotNull($product);

        $oldSlug = $product->slug;
        $newSlug = $oldSlug . '-new-scent';

        // Update slug
        $product->update(['slug' => $newSlug]);

        // Accessing old slug must 301 redirect to new slug
        $response = $this->get("/product/{$oldSlug}");
        $response->assertRedirect("/product/{$newSlug}");
        $response->assertStatus(301);

        // Accessing new slug returns 200 OK
        $newResponse = $this->get("/product/{$newSlug}");
        $newResponse->assertOk();
    }

    public function test_admin_can_manage_pages_in_filament(): void
    {
        $admin = User::first();
        $this->actingAs($admin);

        // Can access list
        $this->get('/admin/pages')->assertOk();

        // Create new page
        $page = Page::create([
            'title' => 'VIP Fragrance Club',
            'slug' => 'vip-fragrance-club',
            'content' => '<p>Exclusive perks for private clients.</p>',
            'meta_title' => 'VIP Club',
            'meta_description' => 'VIP client program',
            'is_published' => true,
        ]);

        $this->get("/admin/pages/{$page->id}/edit")->assertOk();
    }

    public function test_db_optimize_sqlite_artisan_command(): void
    {
        $this->artisan('db:optimize-sqlite')
            ->expectsOutputToContain('SQLite PRAGMA optimize executed successfully')
            ->assertSuccessful();
    }
}
