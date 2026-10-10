<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $price = fake()->randomElement([3500, 4800, 6500, 8900, 12500, 16000]);
        $hasCompare = fake()->boolean(40);

        return [
            'product_id' => Product::factory(),
            'size_ml' => fake()->randomElement([50, 100, 150, 200]),
            'price' => $price,
            'compare_at_price' => $hasCompare ? (int) round($price * 1.25) : null,
            'sku' => 'SKU-' . fake()->unique()->numerify('#####'),
            'stock' => fake()->numberBetween(5, 50),
            'is_active' => true,
        ];
    }
}
