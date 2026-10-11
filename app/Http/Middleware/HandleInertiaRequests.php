<?php

namespace App\Http\Middleware;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $dbSettings = Setting::pluck('value', 'key')->all();

        $settings = array_merge([
            'store_name' => 'Soni Perfume',
            'tagline' => 'Curated Luxury & Everyday Scents in Addis Ababa',
            'about_text' => 'Soni Perfume is Addis Ababa\'s destination for genuine designer and luxury oriental fragrances. Sourced directly from Paris, Italy, and Dubai, every bottle in our collection is 100% authentic with fast same-day delivery across Addis Ababa.',
            'delivery_fee_addis' => '250',
            'free_delivery_threshold' => '5000',
            'welcome_discount_text' => 'Complimentary door-to-door express delivery across Addis Ababa on all orders above ETB 5,000!',
            'opening_hours' => 'Monday – Saturday: 9:00 AM – 8:00 PM | Sunday: 10:00 AM – 6:00 PM',
            'instagram_url' => 'https://instagram.com/soniperfume',
            'tiktok_url' => 'https://tiktok.com/@soniperfume',
            'telegram_url' => 'https://t.me/soniperfume_admin',
            'google_maps_embed' => '',
            'store_phone' => env('STORE_PHONE', '+251 91 123 4567'),
            'store_whatsapp' => env('STORE_WHATSAPP', '+251 91 123 4567'),
            'store_address' => env('STORE_ADDRESS', 'Bole, Addis Ababa, Ethiopia'),
            'store_currency' => env('STORE_CURRENCY', 'ETB'),
        ], $dbSettings);

        $cart = $request->session()->get('cart', []);
        $cartCount = (int) collect($cart)->sum('quantity');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'settings' => $settings,
            'cartCount' => $cartCount,
            'categoriesNav' => fn () => Category::orderBy('sort_order')
                ->select('id', 'name', 'slug')
                ->get(),
            'brandsNav' => fn () => Brand::where('is_featured', true)
                ->select('id', 'name', 'slug')
                ->limit(8)
                ->get(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
