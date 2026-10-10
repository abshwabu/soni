<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Men',
                'slug' => 'men',
                'description' => 'Bold, masculine fragrances and captivating colognes tailored for the modern gentleman.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Women',
                'slug' => 'women',
                'description' => 'Enchanting florals, radiant gourmands, and sophisticated perfumes for every woman.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Unisex',
                'slug' => 'unisex',
                'description' => 'Harmonious, borderless signature scents crafted for perfume lovers without distinction.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Oud & Arabian',
                'slug' => 'oud-arabian',
                'description' => 'Authentic Middle Eastern oud, frankincense, amber, and rich exotic Oriental blends.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Gift Sets',
                'slug' => 'gift-sets',
                'description' => 'Exquisitely boxed fragrance sets ideal for holidays, birthdays, and special occasions.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Body Mists',
                'slug' => 'body-mists',
                'description' => 'Light, uplifting daily all-over body sprays and fragrant mists for everyday hydration and scent.',
                'sort_order' => 6,
            ],
        ];

        foreach ($categories as $index => $catData) {
            $category = Category::updateOrCreate(
                ['slug' => $catData['slug']],
                $catData
            );

            if ($category->getFirstMedia('image') === null) {
                $imagePath = database_path("seeders/images/categories/{$category->slug}.jpg");
                PlaceholderImageGenerator::make(
                    $imagePath,
                    600,
                    600,
                    $category->name,
                    'COLLECTION',
                    $index + 1
                );

                if (file_exists($imagePath)) {
                    $category->addMedia($imagePath)
                        ->preservingOriginal()
                        ->toMediaCollection('image');
                }
            }
        }
    }
}
