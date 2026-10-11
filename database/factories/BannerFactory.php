<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'subtitle' => fake()->sentence(8),
            'button_text' => 'Shop Now',
            'button_url' => '/shop',
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
