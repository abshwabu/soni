<?php

namespace Database\Factories;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomElement([4500, 7800, 12500, 18900]);
        $deliveryFee = fake()->randomElement([0, 250]);

        return [
            'order_number' => Order::generateOrderNumber(),
            'customer_name' => fake()->name(),
            'phone' => '+2519' . fake()->numerify('########'),
            'alt_phone' => fake()->boolean(30) ? '+2519' . fake()->numerify('########') : null,
            'city' => 'Addis Ababa',
            'address' => fake()->streetAddress() . ', Bole Sub-City',
            'notes' => fake()->boolean(20) ? 'Please call before delivery' : null,
            'delivery_method' => fake()->randomElement(DeliveryMethod::cases()),
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $subtotal + $deliveryFee,
            'status' => fake()->randomElement(OrderStatus::cases()),
            'telegram_message_id' => null,
            'telegram_sent_at' => null,
            'ip_address' => fake()->ipv4(),
        ];
    }
}
