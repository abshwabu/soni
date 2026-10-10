<?php

namespace App\Enums;

enum Concentration: string
{
    case EDT = 'EDT';
    case EDP = 'EDP';
    case PARFUM = 'Parfum';
    case OIL = 'Oil';
    case MIST = 'Mist';

    public function label(): string
    {
        return match ($this) {
            self::EDT => 'Eau de Toilette (EDT)',
            self::EDP => 'Eau de Parfum (EDP)',
            self::PARFUM => 'Parfum / Extrait',
            self::OIL => 'Perfume Oil',
            self::MIST => 'Body Mist',
        };
    }
}
