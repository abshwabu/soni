<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'name' => 'Selamawit Tadesse',
                'text' => 'Ordered Baccarat Rouge 540 and received it within 3 hours here in Bole. 100% authentic, batch code verified, and the packaging was immaculate!',
                'rating' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Dawit Haile',
                'text' => 'Club de Nuit Intense Man pure parfum was delivered right to my office in Kazanchis. Fantastic customer service on Telegram, fast confirmation.',
                'rating' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Bethlehem Girma',
                'text' => 'The best perfume boutique in Addis Ababa! Tom Ford Lost Cherry lasts literally all day. The scent projection is incredible. Highly recommended.',
                'rating' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Yonas Bekele',
                'text' => 'Afnan 9 PM is a beast mode compliment puller for evenings out. Easy ordering process without needing online credit card hassle.',
                'rating' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Tigist Worku',
                'text' => 'I was cautious about finding genuine designer perfumes in Addis, but Dior Miss Dior is original French batch. Will definitely be a regular customer!',
                'rating' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Abel Tesfaye',
                'text' => 'Lattafa Khamrah smells like warm roasted cinnamon, praline, and dates. Unbeatable value for money and polite, punctual delivery driver.',
                'rating' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($testimonials as $item) {
            Testimonial::updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
