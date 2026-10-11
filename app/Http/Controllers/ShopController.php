<?php

namespace App\Http\Controllers;

use App\Enums\Concentration;
use App\Enums\Gender;
use App\Enums\ScentFamily;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\SearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Display the main shop catalog or search results.
     */
    public function index(Request $request): Response
    {
        return $this->renderListing($request);
    }

    /**
     * Display products for a specific category.
     */
    public function category(Request $request, string $slug): Response
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        return $this->renderListing($request, category: $category);
    }

    /**
     * Display products for a specific brand.
     */
    public function brand(Request $request, string $slug): Response
    {
        $brand = Brand::where('slug', $slug)->firstOrFail();
        return $this->renderListing($request, brand: $brand);
    }

    /**
     * Display single product detail page.
     */
    public function show(string $slug): Response
    {
        $product = Product::where('slug', $slug)
            ->active()
            ->with([
                'brand',
                'category',
                'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price', 'asc'),
                'media',
            ])
            ->firstOrFail();

        // Related products in same category or brand
        $relatedProducts = Product::active()
            ->where('id', '!=', $product->id)
            ->where(function (Builder $q) use ($product) {
                $q->where('category_id', $product->category_id)
                    ->orWhere('brand_id', $product->brand_id);
            })
            ->with(['brand', 'variants', 'media'])
            ->take(4)
            ->get();

        // Fallback related if fewer than 4
        if ($relatedProducts->count() < 4) {
            $filler = Product::active()
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->with(['brand', 'variants', 'media'])
                ->take(4 - $relatedProducts->count())
                ->get();
            $relatedProducts = $relatedProducts->concat($filler);
        }

        // Clean media list for gallery
        $images = $product->getMedia('images')->map(function ($media) {
            return [
                'id' => $media->id,
                'original_url' => $media->getUrl(),
                'webp_url' => $media->getUrl('webp') ?: $media->getUrl(),
                'thumb_url' => $media->getUrl('thumb') ?: $media->getUrl(),
            ];
        })->values()->all();

        // Fallback placeholder image if no media uploaded
        if (empty($images)) {
            $images[] = [
                'id' => 0,
                'original_url' => '/images/placeholder.svg',
                'webp_url' => '/images/placeholder.svg',
                'thumb_url' => '/images/placeholder.svg',
            ];
        }

        $metaTitle = $product->meta_title
            ?: "{$product->name} by {$product->brand?->name} | Soni Perfume Addis Ababa";

        $metaDescription = $product->meta_description
            ?: Str::limit(strip_tags($product->short_description ?: $product->description), 160);

        return Inertia::render('Product/Show', [
            'product' => $product,
            'images' => $images,
            'relatedProducts' => $relatedProducts,
            'seo' => [
                'title' => $metaTitle,
                'description' => $metaDescription,
                'image' => $product->primary_image_url,
                'url' => url()->current(),
            ],
        ]);
    }

    /**
     * Render the filtered and paginated catalog listing.
     */
    protected function renderListing(Request $request, ?Category $category = null, ?Brand $brand = null): Response
    {
        $query = Product::active()
            ->with([
                'brand',
                'category',
                'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price', 'asc'),
                'media',
            ]);

        // Search query filter
        $searchQuery = trim((string) $request->input('q', ''));
        if ($searchQuery !== '') {
            $matchedIds = $this->searchService->searchProductIds($searchQuery);
            $query->whereIn('id', $matchedIds);
        }

        // Category filter
        if ($category) {
            $query->where('category_id', $category->id);
        } elseif ($request->filled('category')) {
            $catInputs = is_array($request->input('category'))
                ? $request->input('category')
                : explode(',', (string) $request->input('category'));
            $query->whereHas('category', fn ($q) => $q->whereIn('slug', $catInputs)->orWhereIn('id', $catInputs));
        }

        // Brand filter
        if ($brand) {
            $query->where('brand_id', $brand->id);
        } elseif ($request->filled('brand')) {
            $brandInputs = is_array($request->input('brand'))
                ? $request->input('brand')
                : explode(',', (string) $request->input('brand'));
            $query->whereHas('brand', fn ($q) => $q->whereIn('slug', $brandInputs)->orWhereIn('id', $brandInputs));
        }

        // Gender filter
        if ($request->filled('gender')) {
            $genders = (array) $request->input('gender');
            $query->whereIn('gender', $genders);
        }

        // Scent Family filter
        if ($request->filled('scent_family')) {
            $families = (array) $request->input('scent_family');
            $query->whereIn('scent_family', $families);
        }

        // Concentration filter
        if ($request->filled('concentration')) {
            $concentrations = (array) $request->input('concentration');
            $query->whereIn('concentration', $concentrations);
        }

        // Price Min filter
        if ($request->filled('price_min') && is_numeric($request->input('price_min'))) {
            $min = (int) $request->input('price_min');
            $query->whereHas('variants', fn ($q) => $q->where('is_active', true)->where('price', '>=', $min));
        }

        // Price Max filter
        if ($request->filled('price_max') && is_numeric($request->input('price_max'))) {
            $max = (int) $request->input('price_max');
            $query->whereHas('variants', fn ($q) => $q->where('is_active', true)->where('price', '<=', $max));
        }

        // Size (ml) filter
        if ($request->filled('size')) {
            $sizes = is_array($request->input('size'))
                ? $request->input('size')
                : explode(',', (string) $request->input('size'));
            $query->whereHas('variants', fn ($q) => $q->where('is_active', true)->whereIn('size_ml', $sizes));
        }

        // In Stock Only filter
        if ($request->boolean('in_stock')) {
            $query->inStock();
        }

        // Sorting
        $sort = (string) $request->input('sort', 'newest');
        switch ($sort) {
            case 'bestsellers':
                $query->orderByDesc('is_bestseller')->latest('id');
                break;
            case 'price_asc':
                $priceSub = '(SELECT MIN(price) FROM product_variants WHERE product_variants.product_id = products.id AND product_variants.is_active = 1)';
                $query->select('products.*')->selectRaw("({$priceSub}) as sort_price")->orderBy('sort_price', 'asc');
                break;
            case 'price_desc':
                $priceSub = '(SELECT MIN(price) FROM product_variants WHERE product_variants.product_id = products.id AND product_variants.is_active = 1)';
                $query->select('products.*')->selectRaw("({$priceSub}) as sort_price")->orderBy('sort_price', 'desc');
                break;
            case 'newest':
            default:
                $query->latest('id');
                break;
        }

        $products = $query->paginate(12)->withQueryString();

        // Filter options for the UI
        $filterOptions = [
            'categories' => Category::ordered()->withCount(['products' => fn ($q) => $q->active()])->get(['id', 'name', 'slug', 'products_count']),
            'brands' => Brand::orderBy('name')->withCount(['products' => fn ($q) => $q->active()])->get(['id', 'name', 'slug', 'products_count']),
            'genders' => [
                ['value' => 'men', 'label' => 'Men'],
                ['value' => 'women', 'label' => 'Women'],
                ['value' => 'unisex', 'label' => 'Unisex'],
            ],
            'scentFamilies' => array_map(fn ($case) => [
                'value' => $case->value,
                'label' => ucfirst($case->value),
            ], ScentFamily::cases()),
            'concentrations' => array_map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->value,
            ], Concentration::cases()),
            'sizes' => ProductVariant::where('is_active', true)
                ->distinct()
                ->orderBy('size_ml')
                ->pluck('size_ml')
                ->values()
                ->all(),
            'priceBounds' => [
                'min' => (int) (ProductVariant::where('is_active', true)->min('price') ?? 0),
                'max' => (int) (ProductVariant::where('is_active', true)->max('price') ?? 50000),
            ],
        ];

        // Dynamic page title & SEO
        if ($category) {
            $pageTitle = "{$category->name} Fragrances";
            $seoTitle = "{$category->name} Perfumes in Addis Ababa | Soni Perfume";
            $seoDescription = $category->description ?: "Shop authentic {$category->name} perfumes and fragrances in Addis Ababa, Ethiopia.";
        } elseif ($brand) {
            $pageTitle = "{$brand->name} Perfumes";
            $seoTitle = "{$brand->name} Fragrances in Addis Ababa | Soni Perfume";
            $seoDescription = $brand->description ?: "Discover 100% authentic {$brand->name} fragrances at Soni Perfume Addis Ababa.";
        } elseif ($searchQuery !== '') {
            $pageTitle = "Search: \"{$searchQuery}\"";
            $seoTitle = "Search results for \"{$searchQuery}\" | Soni Perfume";
            $seoDescription = "Explore fragrance search results for \"{$searchQuery}\" at Soni Perfume in Addis Ababa.";
        } else {
            $pageTitle = 'Shop Fragrances';
            $seoTitle = 'Shop Luxury Fragrances in Addis Ababa | Soni Perfume';
            $seoDescription = 'Explore authentic luxury, designer, and Arabian perfumes in Addis Ababa. Same-day delivery, cash on delivery.';
        }

        return Inertia::render('Shop/Index', [
            'products' => $products,
            'filters' => [
                'q' => $searchQuery,
                'category' => $category ? $category->slug : $request->input('category'),
                'brand' => $brand ? $brand->slug : $request->input('brand'),
                'gender' => $request->input('gender'),
                'scent_family' => $request->input('scent_family'),
                'concentration' => $request->input('concentration'),
                'price_min' => $request->input('price_min'),
                'price_max' => $request->input('price_max'),
                'size' => $request->input('size'),
                'in_stock' => $request->boolean('in_stock'),
                'sort' => $sort,
            ],
            'filterOptions' => $filterOptions,
            'currentCategory' => $category,
            'currentBrand' => $brand,
            'searchQuery' => $searchQuery,
            'pageTitle' => $pageTitle,
            'seo' => [
                'title' => $seoTitle,
                'description' => $seoDescription,
                'url' => url()->current(),
            ],
        ]);
    }
}
