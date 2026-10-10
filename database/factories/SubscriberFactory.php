<?php

namespace Database\Factories;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscriber>
 */
class SubscriberFactory extends Factory
{
    protected $model = Subscriber::class;

    public function definition(): array
    {
        return [
            'email_or_phone' => fake()->boolean(50)
                ? fake()->unique()->safeEmail()
                : '+2519' . fake()->unique()->numerify('########'),
            'source' => 'website',
        ];
    }
}
