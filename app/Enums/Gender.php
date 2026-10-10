<?php

namespace App\Enums;

enum Gender: string
{
    case MEN = 'men';
    case WOMEN = 'women';
    case UNISEX = 'unisex';

    public function label(): string
    {
        return match ($this) {
            self::MEN => 'Men',
            self::WOMEN => 'Women',
            self::UNISEX => 'Unisex',
        };
    }
}
