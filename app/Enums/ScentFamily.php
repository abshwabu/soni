<?php

namespace App\Enums;

enum ScentFamily: string
{
    case FLORAL = 'floral';
    case WOODY = 'woody';
    case ORIENTAL = 'oriental';
    case FRESH = 'fresh';
    case CITRUS = 'citrus';
    case GOURMAND = 'gourmand';
    case MUSK = 'musk';

    public function label(): string
    {
        return match ($this) {
            self::FLORAL => 'Floral',
            self::WOODY => 'Woody',
            self::ORIENTAL => 'Oriental',
            self::FRESH => 'Fresh',
            self::CITRUS => 'Citrus',
            self::GOURMAND => 'Gourmand',
            self::MUSK => 'Musk',
        };
    }
}
