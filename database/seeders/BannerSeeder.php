<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'title' => 'Find Your Signature Fragrance',
                'subtitle' => 'Curated collection of 100% authentic designer and niche perfumes in Addis Ababa.',
                'button_text' => 'Explore Fragrances',
                'button_url' => '/catalog',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'The Art of Arabian Oud',
                'subtitle' => 'Rich, intoxicating Middle Eastern orientals, rare agarwood, and amber treasures.',
                'button_text' => 'Discover Oud',
                'button_url' => '/catalog?category=oud-arabian',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Most Loved Bestsellers',
                'subtitle' => 'Iconic compliment pullers adored by fragrance connoisseurs across Ethiopia.',
                'button_text' => 'Shop Bestsellers',
                'button_url' => '/catalog?filter=bestseller',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Doorstep Delivery in Addis Ababa',
                'subtitle' => 'Same-day and next-day express delivery right to your door with Telegram order tracking.',
                'button_text' => 'Order Now',
                'button_url' => '/catalog',
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($banners as $index => $bannerData) {
            $banner = Banner::updateOrCreate(
                ['title' => $bannerData['title']],
                $bannerData
            );

            if ($banner->getFirstMedia('image') === null) {
                $imagePath = database_path("seeders/images/banners/banner-{$banner->id}.jpg");
                PlaceholderImageGenerator::make(
                    $imagePath,
                    1920,
                    800,
                    $banner->title,
                    'SONI PERFUME ADDIS ABABA',
                    $index
                );

                if (file_exists($imagePath)) {
                    $banner->addMedia($imagePath)
                        ->preservingOriginal()
                        ->toMediaCollection('image');
                }
            }
        }
    }
}
