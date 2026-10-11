<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\SearchService;
use Tests\TestCase;

class ShopAndSearchTest extends TestCase
{
    public function test_shop_listing_returns_products_with_filters_and_filter_options(): void
    {
        $response = $this->get('/shop');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Shop/Index')
            ->has('products')
            ->has('products.data')
            ->has('filters')
            ->has('filterOptions')
            ->has('filterOptions.categories')
            ->has('filterOptions.brands')
            ->has('filterOptions.scentFamilies')
            ->has('filterOptions.concentrations')
            ->has('filterOptions.sizes')
            ->has('filterOptions.priceBounds')
            ->has('pageTitle')
            ->has('seo')
        );
    }

    public function test_category_page_returns_filtered_products_and_category_metadata(): void
    {
        $category = Category::first();
        $this->assertNotNull($category);

        $response = $this->get("/category/{$category->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Shop/Index')
            ->has('products')
            ->where('currentCategory.id', $category->id)
            ->where('currentCategory.slug', $category->slug)
        );
    }

    public function test_brand_page_returns_filtered_products_and_brand_metadata(): void
    {
        $brand = Brand::first();
        $this->assertNotNull($brand);

        $response = $this->get("/brand/{$brand->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Shop/Index')
            ->has('products')
            ->where('currentBrand.id', $brand->id)
            ->where('currentBrand.slug', $brand->slug)
        );
    }

    public function test_brands_index_directory_returns_all_brands_grouped(): void
    {
        $response = $this->get('/brands');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Brands/Index')
            ->has('featuredBrands')
            ->has('groupedBrands')
            ->has('totalBrands')
            ->has('seo')
        );
    }

    public function test_product_detail_page_renders_with_variants_and_related_products(): void
    {
        $product = Product::active()->first();
        $this->assertNotNull($product);

        $response = $this->get("/product/{$product->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Product/Show')
            ->has('product')
            ->where('product.id', $product->id)
            ->has('product.variants')
            ->has('images')
            ->has('relatedProducts')
            ->has('seo')
        );
    }

    public function test_search_suggestions_endpoint_returns_json_products_and_brands(): void
    {
        $response = $this->getJson('/search/suggestions?q=sauv');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'products',
            'brands',
            'total',
        ]);
    }

    public function test_fts5_search_finds_oud_sauvage_and_partial_words(): void
    {
        $searchService = app(SearchService::class);
        $searchService->reindexAll();

        // 1. Partial word search "sauv"
        $sauvMatches = $searchService->searchProductIds('sauv');
        $this->assertNotEmpty($sauvMatches, 'Search should find partial word "sauv"');

        // 2. Full word "sauvage"
        $sauvageMatches = $searchService->searchProductIds('sauvage');
        $this->assertNotEmpty($sauvageMatches, 'Search should find "sauvage"');

        // 3. Keyword "oud"
        $oudMatches = $searchService->searchProductIds('oud');
        $this->assertNotEmpty($oudMatches, 'Search should find "oud"');

        // Verify search route
        $response = $this->get('/search?q=sauv');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Shop/Index')
            ->where('searchQuery', 'sauv')
            ->has('products.data')
        );
    }

    public function test_search_reindex_artisan_command_executes_successfully(): void
    {
        $this->artisan('search:reindex')
            ->expectsOutputToContain('Successfully indexed')
            ->assertSuccessful();
    }

    public function test_shop_filtering_and_sorting_combines_properly(): void
    {
        // Low to high price sort
        $responseAsc = $this->get('/shop?sort=price_asc');
        $responseAsc->assertStatus(200);

        // High to low price sort
        $responseDesc = $this->get('/shop?sort=price_desc');
        $responseDesc->assertStatus(200);

        // In-stock and gender filter
        $responseFilter = $this->get('/shop?gender=men&in_stock=1');
        $responseFilter->assertStatus(200);
        $responseFilter->assertInertia(fn ($page) => $page
            ->component('Shop/Index')
            ->where('filters.gender', 'men')
            ->where('filters.in_stock', true)
        );
    }
}
