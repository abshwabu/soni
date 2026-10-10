<?php

namespace Database\Seeders;

class PlaceholderImageGenerator
{
    protected static array $palettes = [
        ['bg' => [28, 25, 23], 'accent' => [217, 119, 6], 'text' => [250, 250, 249]],    // Amber / Charcoal
        ['bg' => [15, 23, 42], 'accent' => [56, 189, 248], 'text' => [241, 245, 249]],   // Sapphire / Midnight
        ['bg' => [67, 20, 20], 'accent' => [244, 114, 182], 'text' => [253, 242, 248]],  // Burgundy / Rose
        ['bg' => [20, 40, 30], 'accent' => [52, 211, 153], 'text' => [236, 253, 245]],   // Emerald / Forest
        ['bg' => [41, 37, 36], 'accent' => [251, 191, 36], 'text' => [254, 243, 199]],   // Warm Stone / Gold
        ['bg' => [30, 27, 46], 'accent' => [192, 132, 252], 'text' => [250, 245, 255]],  // Amethyst / Violet
    ];

    public static function make(string $path, int $width, int $height, string $label, string $sublabel = '', int $paletteIndex = 0): string
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        if (file_exists($path)) {
            return $path;
        }

        $palette = static::$palettes[$paletteIndex % count(static::$palettes)];

        $image = imagecreatetruecolor($width, $height);
        $bgColor = imagecolorallocate($image, $palette['bg'][0], $palette['bg'][1], $palette['bg'][2]);
        $accentColor = imagecolorallocate($image, $palette['accent'][0], $palette['accent'][1], $palette['accent'][2]);
        $textColor = imagecolorallocate($image, $palette['text'][0], $palette['text'][1], $palette['text'][2]);

        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        // Decorative borders
        $borderPadding = (int) max(10, min($width, $height) * 0.04);
        imagerectangle($image, $borderPadding, $borderPadding, $width - $borderPadding, $height - $borderPadding, $accentColor);

        // Center perfume bottle silhouette / stylized icon
        $cx = (int) ($width / 2);
        $cy = (int) ($height / 2);

        // Bottle cap
        $capWidth = (int) ($width * 0.12);
        $capHeight = (int) ($height * 0.08);
        imagefilledrectangle($image, $cx - (int)($capWidth / 2), $cy - (int)($height * 0.25), $cx + (int)($capWidth / 2), $cy - (int)($height * 0.25) + $capHeight, $accentColor);

        // Bottle body
        $bodyWidth = (int) ($width * 0.32);
        $bodyHeight = (int) ($height * 0.32);
        imagefilledrectangle($image, $cx - (int)($bodyWidth / 2), $cy - (int)($height * 0.14), $cx + (int)($bodyWidth / 2), $cy - (int)($height * 0.14) + $bodyHeight, $accentColor);

        // Inner cutout for elegance
        $innerMargin = 4;
        imagefilledrectangle($image, $cx - (int)($bodyWidth / 2) + $innerMargin, $cy - (int)($height * 0.14) + $innerMargin, $cx + (int)($bodyWidth / 2) - $innerMargin, $cy - (int)($height * 0.14) + $bodyHeight - $innerMargin, $bgColor);

        // Text labels
        $font = 5;
        $labelLength = strlen($label) * imagefontwidth($font);
        $labelX = max(10, $cx - (int)($labelLength / 2));
        $labelY = min($height - 60, $cy + (int)($bodyHeight / 2) + 20);
        imagestring($image, $font, $labelX, $labelY, $label, $textColor);

        if ($sublabel !== '') {
            $subFont = 3;
            $subLength = strlen($sublabel) * imagefontwidth($subFont);
            $subX = max(10, $cx - (int)($subLength / 2));
            $subY = $labelY + 22;
            imagestring($image, $subFont, $subX, $subY, $sublabel, $accentColor);
        }

        if (str_ends_with($path, '.png')) {
            imagepng($image, $path, 8);
        } else {
            imagejpeg($image, $path, 90);
        }

        imagedestroy($image);

        return $path;
    }
}
