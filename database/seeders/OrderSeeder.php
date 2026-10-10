<?php

namespace Database\Seeders;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::with('variants')->has('variants')->get();

        if ($products->isEmpty()) {
            return;
        }

        $sampleOrders = [
            [
                'customer_name' => 'Abebe Bikila',
                'phone' => '+251911223344',
                'alt_phone' => '+251922334455',
                'city' => 'Addis Ababa',
                'address' => 'Bole Medhanealem, Atlas Hotel area, House #402',
                'notes' => 'Please deliver between 2 PM and 5 PM.',
                'delivery_method' => DeliveryMethod::DELIVERY,
                'status' => OrderStatus::NEW,
                'telegram_message_id' => '10041',
                'telegram_sent_at' => now()->subHours(2),
            ],
            [
                'customer_name' => 'Selamawit Desta',
                'phone' => '+251933445566',
                'alt_phone' => null,
                'city' => 'Addis Ababa',
                'address' => 'Old Airport, near South African Embassy',
                'notes' => 'Call on arrival at the gate.',
                'delivery_method' => DeliveryMethod::DELIVERY,
                'status' => OrderStatus::CONFIRMED,
                'telegram_message_id' => '10042',
                'telegram_sent_at' => now()->subHours(5),
            ],
            [
                'customer_name' => 'Yonas Tadesse',
                'phone' => '+251944556677',
                'alt_phone' => null,
                'city' => 'Addis Ababa',
                'address' => 'Kazanchis, UNECA compound gate 3',
                'notes' => null,
                'delivery_method' => DeliveryMethod::DELIVERY,
                'status' => OrderStatus::OUT_FOR_DELIVERY,
                'telegram_message_id' => '10043',
                'telegram_sent_at' => now()->subHours(8),
            ],
            [
                'customer_name' => 'Hanna Tesfaye',
                'phone' => '+251955667788',
                'alt_phone' => '+251911998877',
                'city' => 'Addis Ababa',
                'address' => 'Store Pickup (Bole Edna Mall Branch)',
                'notes' => 'Will pick up in person after 6 PM.',
                'delivery_method' => DeliveryMethod::PICKUP,
                'status' => OrderStatus::COMPLETED,
                'telegram_message_id' => '10044',
                'telegram_sent_at' => now()->subDays(1),
            ],
            [
                'customer_name' => 'Dawit Kebede',
                'phone' => '+251966778899',
                'alt_phone' => null,
                'city' => 'Addis Ababa',
                'address' => 'Sarbet, Adams Pavilion, 4th floor',
                'notes' => 'Gift packaging requested please.',
                'delivery_method' => DeliveryMethod::DELIVERY,
                'status' => OrderStatus::COMPLETED,
                'telegram_message_id' => '10045',
                'telegram_sent_at' => now()->subDays(2),
            ],
            [
                'customer_name' => 'Bethelhem Girma',
                'phone' => '+251977889900',
                'alt_phone' => null,
                'city' => 'Addis Ababa',
                'address' => 'CMC Michael, Sunshine Apartments block 12',
                'notes' => null,
                'delivery_method' => DeliveryMethod::DELIVERY,
                'status' => OrderStatus::CANCELLED,
                'telegram_message_id' => null,
                'telegram_sent_at' => null,
            ],
        ];

        foreach ($sampleOrders as $orderData) {
            $order = Order::create(array_merge($orderData, [
                'subtotal' => 0,
                'delivery_fee' => $orderData['delivery_method'] === DeliveryMethod::DELIVERY ? 250 : 0,
                'total' => 0,
                'ip_address' => '127.0.0.1',
            ]));

            // Pick 1 to 3 random products
            $chosenProducts = $products->random(min(2, $products->count()));
            $subtotal = 0;

            foreach ($chosenProducts as $product) {
                $variant = $product->variants->first();
                if (!$variant) {
                    continue;
                }

                $quantity = rand(1, 2);
                $unitPrice = $variant->price;
                $lineTotal = $unitPrice * $quantity;
                $subtotal += $lineTotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $product->name,
                    'variant_label' => "{$variant->size_ml}ml",
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ]);
            }

            $deliveryFee = ($subtotal >= 5000 || $order->delivery_method === DeliveryMethod::PICKUP) ? 0 : 250;
            $order->update([
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $subtotal + $deliveryFee,
            ]);
        }
    }
}
