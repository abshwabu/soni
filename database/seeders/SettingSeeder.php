<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
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
            'google_maps_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.5!2d38.789!3d9.001!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zOcKwMDAnMDMuNiJOIDM4wrA0NycyMC40IkU!5e0!3m2!1sen!2set!4v1',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
