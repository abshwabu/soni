<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Display the landing storefront page.
     */
    public function index(): Response
    {
        $homeData = \Illuminate\Support\Facades\Cache::remember('home_page_data', 1800, function () {
            $banners = Banner::active()->ordered()->get();

            $categories = Category::ordered()
                ->withCount(['products' => fn ($q) => $q->active()])
                ->get();

            // Bestsellers with fallback to active products
            $bestsellers = Product::active()
                ->bestseller()
                ->with(['brand', 'variants', 'media'])
                ->take(8)
                ->get();

            if ($bestsellers->count() < 4) {
                $extra = Product::active()
                    ->whereNotIn('id', $bestsellers->pluck('id'))
                    ->with(['brand', 'variants', 'media'])
                    ->take(8 - $bestsellers->count())
                    ->get();
                $bestsellers = $bestsellers->concat($extra);
            }

            // New Arrivals with fallback
            $newArrivals = Product::active()
                ->new()
                ->with(['brand', 'variants', 'media'])
                ->latest('id')
                ->take(8)
                ->get();

            if ($newArrivals->count() < 4) {
                $extraNew = Product::active()
                    ->whereNotIn('id', $newArrivals->pluck('id'))
                    ->with(['brand', 'variants', 'media'])
                    ->latest('id')
                    ->take(8 - $newArrivals->count())
                    ->get();
                $newArrivals = $newArrivals->concat($extraNew);
            }

            // Featured Brands
            $featuredBrands = Brand::featured()
                ->withCount(['products' => fn ($q) => $q->active()])
                ->get();

            // Gift Sets highlight
            $giftSets = Product::active()
                ->whereHas('category', fn ($q) => $q->where('slug', 'gift-sets'))
                ->with(['brand', 'variants', 'media'])
                ->take(4)
                ->get();

            if ($giftSets->isEmpty()) {
                $giftSets = Product::active()
                    ->with(['brand', 'variants', 'media'])
                    ->take(4)
                    ->get();
            }

            // Testimonials
            $testimonials = Testimonial::active()
                ->latest('id')
                ->take(6)
                ->get();

            // Products pool for Scent Finder quiz matching
            $quizProducts = Product::active()
                ->with(['brand', 'variants', 'media'])
                ->take(30)
                ->get();

            return [
                'banners' => $banners,
                'categories' => $categories,
                'bestsellers' => $bestsellers,
                'featuredProducts' => $bestsellers,
                'newArrivals' => $newArrivals,
                'featuredBrands' => $featuredBrands,
                'giftSets' => $giftSets,
                'testimonials' => $testimonials,
                'quizProducts' => $quizProducts,
            ];
        });

        return Inertia::render('Home', $homeData);
    }

    /**
     * Claim first-visit welcome promotion.
     */
    public function claimWelcomeOffer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_or_phone' => ['required', 'string', 'max:255'],
        ]);

        $emailOrPhone = trim($validated['email_or_phone']);

        Subscriber::firstOrCreate(
            ['email_or_phone' => $emailOrPhone],
            ['source' => 'welcome_modal_offer']
        );

        session(['welcome_offer' => true]);

        return response()->json([
            'success' => true,
            'promo_code' => 'SONIWELCOME',
            'message' => 'First-order offer claimed! A complimentary luxury sample vial will be included with your order.',
        ]);
    }
}
