<?php

namespace Database\Factories;

use App\Enums\Concentration;
use App\Enums\Gender;
use App\Enums\ScentFamily;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'gender' => fake()->randomElement(Gender::cases()),
            'concentration' => fake()->randomElement(Concentration::cases()),
            'top_notes' => 'Bergamot, Pink Pepper, Lemon',
            'heart_notes' => 'Damask Rose, Jasmine, Lavender',
            'base_notes' => 'Vanilla, Amber, Oud, White Musk',
            'scent_family' => fake()->randomElement(ScentFamily::cases()),
            'is_featured' => fake()->boolean(25),
            'is_new' => fake()->boolean(20),
            'is_bestseller' => fake()->boolean(25),
            'is_active' => true,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }
}
