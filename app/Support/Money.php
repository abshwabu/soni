<?php

namespace App\Support;

class Money
{
    /**
     * Format a whole ETB integer into standard display format (e.g., "ETB 1,250").
     */
    public static function format(?int $amount, string $currency = 'ETB'): string
    {
        if ($amount === null) {
            return '';
        }

        return sprintf('%s %s', $currency, number_format($amount));
    }
}
