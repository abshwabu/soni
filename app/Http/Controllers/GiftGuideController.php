<?php

namespace App\Http\Controllers;

use App\Enums\Concentration;
use App\Enums\Gender;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class GiftGuideController extends Controller
{
    /**
     * Display the curated fragrance gift guide.
     */
    public function index(): Response
    {
        $baseQuery = fn () => Product::active()
            ->with([
                'brand',
                'category',
                'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price', 'asc'),
                'media',
            ]);

        $forHim = $baseQuery()
            ->whereIn('gender', [Gender::MEN, Gender::UNISEX])
            ->latest('id')
            ->take(8)
            ->get();

        $forHer = $baseQuery()
            ->whereIn('gender', [Gender::WOMEN, Gender::UNISEX])
            ->latest('id')
            ->take(8)
            ->get();

        $under3000 = $baseQuery()
            ->whereHas('variants', fn ($q) => $q->where('price', '<=', 3000)->where('is_active', true))
            ->latest('id')
            ->take(8)
            ->get();

        $luxury = $baseQuery()
            ->where(function ($q) {
                $q->whereHas('variants', fn ($v) => $v->where('price', '>=', 7000)->where('is_active', true))
                    ->orWhereIn('concentration', [Concentration::PARFUM, Concentration::OIL]);
            })
            ->latest('id')
            ->take(8)
            ->get();

        $giftSets = $baseQuery()
            ->whereHas('category', fn ($c) => $c->where('slug', 'gift-sets'))
            ->latest('id')
            ->take(8)
            ->get();

        return Inertia::render('Gifts', [
            'forHim' => $forHim,
            'forHer' => $forHer,
            'under3000' => $under3000,
            'luxury' => $luxury,
            'giftSets' => $giftSets,
            'seo' => [
                'title' => 'Fragrance Gift Guide | Curated Luxury Perfumes in Addis Ababa',
                'description' => 'Explore the Soni Perfume gift guide: curated signature scents for him, for her, under ETB 3,000, and luxury statement fragrances with complimentary gift wrapping in Addis Ababa.',
                'url' => route('gifts.index'),
            ],
        ]);
    }
}
