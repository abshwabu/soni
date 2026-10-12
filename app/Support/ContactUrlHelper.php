<?php

namespace App\Support;

class ContactUrlHelper
{
    /**
     * Format a WhatsApp redirect URL from phone number, username, or URL.
     */
    public static function formatWhatsAppUrl(?string $input, ?string $prefilledMessage = null): string
    {
        $input = trim((string) ($input ?? ''));

        if ($input === '') {
            $input = '+251911234567';
        }

        // Full URL already provided
        if (str_starts_with($input, 'http://') || str_starts_with($input, 'https://')) {
            if ($prefilledMessage && !str_contains($input, 'text=')) {
                $separator = str_contains($input, '?') ? '&' : '?';
                return $input . $separator . 'text=' . urlencode($prefilledMessage);
            }
            return $input;
        }

        if (str_starts_with($input, 'wa.me/')) {
            $url = 'https://' . $input;
            if ($prefilledMessage && !str_contains($url, 'text=')) {
                $separator = str_contains($url, '?') ? '&' : '?';
                return $url . $separator . 'text=' . urlencode($prefilledMessage);
            }
            return $url;
        }

        // Normalize phone digits
        $digits = preg_replace('/\D/', '', $input);

        // Normalize local Ethiopian mobile numbers: 09... or 07... -> 2519... or 2517...
        if ((str_starts_with($digits, '09') || str_starts_with($digits, '07')) && strlen($digits) === 10) {
            $digits = '251' . substr($digits, 1);
        } elseif ((str_starts_with($digits, '9') || str_starts_with($digits, '7')) && strlen($digits) === 9) {
            $digits = '251' . $digits;
        }

        $base = 'https://wa.me/' . $digits;

        if ($prefilledMessage) {
            return $base . '?text=' . urlencode($prefilledMessage);
        }

        return $base;
    }

    /**
     * Format a Telegram redirect URL from username, phone number, or URL.
     */
    public static function formatTelegramUrl(?string $input, ?string $prefilledMessage = null): string
    {
        $input = trim((string) ($input ?? ''));

        if ($input === '') {
            $input = 'soniperfume_admin';
        }

        // Full URL already provided
        if (str_starts_with($input, 'http://') || str_starts_with($input, 'https://') || str_starts_with($input, 'tg://')) {
            if ($prefilledMessage && !str_contains($input, 'text=')) {
                $separator = str_contains($input, '?') ? '&' : '?';
                return $input . $separator . 'text=' . urlencode($prefilledMessage);
            }
            return $input;
        }

        if (str_starts_with($input, 't.me/')) {
            $url = 'https://' . $input;
            if ($prefilledMessage && !str_contains($url, 'text=')) {
                $separator = str_contains($url, '?') ? '&' : '?';
                return $url . $separator . 'text=' . urlencode($prefilledMessage);
            }
            return $url;
        }

        $digits = preg_replace('/\D/', '', $input);

        // If the input consists purely of phone-like characters (digits, +, spaces, dashes) and has >= 8 digits
        if (preg_match('/^[\d\s+\-()]+$/', $input) && strlen($digits) >= 8) {
            // Local Ethiopian phone format
            if ((str_starts_with($digits, '09') || str_starts_with($digits, '07')) && strlen($digits) === 10) {
                $digits = '251' . substr($digits, 1);
            } elseif ((str_starts_with($digits, '9') || str_starts_with($digits, '7')) && strlen($digits) === 9) {
                $digits = '251' . $digits;
            }

            $url = 'https://t.me/+' . $digits;
            if ($prefilledMessage) {
                return $url . '?text=' . urlencode($prefilledMessage);
            }
            return $url;
        }

        // Otherwise treat as Telegram username (strip leading @)
        $username = ltrim($input, '@');
        $url = 'https://t.me/' . $username;

        if ($prefilledMessage) {
            return $url . '?text=' . urlencode($prefilledMessage);
        }

        return $url;
    }
}
