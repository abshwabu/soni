<?php

namespace App\Casts;

use App\Support\Money as MoneyHelper;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class Money implements CastsAttributes
{
    /**
     * Cast the given value from database storage.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return $value !== null ? (int) $value : null;
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = preg_replace('/[^0-9]/', '', $value);
        }

        return (int) $value;
    }

    /**
     * Format money value.
     */
    public static function format(?int $amount, string $currency = 'ETB'): string
    {
        return MoneyHelper::format($amount, $currency);
    }
}
