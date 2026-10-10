<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Dior',
                'slug' => 'dior',
                'description' => 'Iconic French luxury fashion house renowned for Sauvage, Miss Dior, and timeless haute perfumery.',
                'is_featured' => true,
            ],
            [
                'name' => 'Chanel',
                'slug' => 'chanel',
                'description' => 'Legendary Parisian luxury known worldwide for Bleu de Chanel, Coco Mademoiselle, and Chanel No. 5.',
                'is_featured' => true,
            ],
            [
                'name' => 'Tom Ford',
                'slug' => 'tom-ford',
                'description' => 'Provocative, opulent, and modern luxury perfumery celebrated for Tobacco Vanille, Oud Wood, and Black Orchid.',
                'is_featured' => true,
            ],
            [
                'name' => 'Creed',
                'slug' => 'creed',
                'description' => 'Historic Anglo-French luxury perfume dynasty famed for Aventus, Silver Mountain Water, and Green Irish Tweed.',
                'is_featured' => true,
            ],
            [
                'name' => 'Maison Francis Kurkdjian',
                'slug' => 'maison-francis-kurkdjian',
                'description' => 'Sublime Parisian niche perfumery creator of the iconic Baccarat Rouge 540 and Grand Soir.',
                'is_featured' => true,
            ],
            [
                'name' => 'Lattafa',
                'slug' => 'lattafa',
                'description' => 'Premier Dubai fragrance house delivering luxurious Middle Eastern oud, amber, and gourmand masterpieces.',
                'is_featured' => true,
            ],
            [
                'name' => 'Afnan',
                'slug' => 'afnan',
                'description' => 'Contemporary UAE perfumery crafting trendsetting fragrances with long-lasting sillage like 9 PM.',
                'is_featured' => false,
            ],
            [
                'name' => 'Armaf',
                'slug' => 'armaf',
                'description' => 'Globally acclaimed fragrance house celebrated for Club de Nuit Intense Man and modern niche-quality scents.',
                'is_featured' => false,
            ],
        ];

        foreach ($brands as $index => $brandData) {
            $brand = Brand::updateOrCreate(
                ['slug' => $brandData['slug']],
                $brandData
            );

            // Generate and attach placeholder logo if not already present
            if ($brand->getFirstMedia('logo') === null) {
                $imagePath = database_path("seeders/images/brands/{$brand->slug}.png");
                PlaceholderImageGenerator::make(
                    $imagePath,
                    400,
                    400,
                    $brand->name,
                    'PERFUMES',
                    $index
                );

                if (file_exists($imagePath)) {
                    $brand->addMedia($imagePath)
                        ->preservingOriginal()
                        ->toMediaCollection('logo');
                }
            }
        }
    }
}
