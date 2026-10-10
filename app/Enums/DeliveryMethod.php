<?php

namespace App\Enums;

enum DeliveryMethod: string
{
    case DELIVERY = 'delivery';
    case PICKUP = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::DELIVERY => 'Doorstep Delivery',
            self::PICKUP => 'Store Pickup',
        };
    }
}
