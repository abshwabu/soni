<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    /**
     * Display the brands index directory.
     */
    public function index(): Response
    {
        $brands = Brand::withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('name')
            ->get();

        $featuredBrands = $brands->where('is_featured', true)->values();

        // Group alphabetically by first character
        $grouped = $brands->groupBy(function ($brand) {
            $first = strtoupper(mb_substr($brand->name, 0, 1));
            return ctype_alpha($first) ? $first : '#';
        })->sortKeys();

        return Inertia::render('Brands/Index', [
            'featuredBrands' => $featuredBrands,
            'groupedBrands' => $grouped,
            'totalBrands' => $brands->count(),
            'seo' => [
                'title' => 'Fragrance Brands & Designers | Soni Perfume Addis Ababa',
                'description' => 'Explore luxury, designer, and niche fragrance houses available at Soni Perfume in Addis Ababa. 100% authentic perfumes from Dior, Chanel, Lattafa, Tom Ford, and more.',
            ],
        ]);
    }
}
