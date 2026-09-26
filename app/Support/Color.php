<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Hex colour helpers. Templates compute tints server-side because mPDF has no
 * CSS variables and only partial rgba() support.
 */
final class Color
{
    /**
     * Mixes $color towards $with; $amount 0 keeps $color, 1 returns $with.
     */
    public static function mix(string $color, string $with, float $amount): string
    {
        [$r1, $g1, $b1] = self::rgb($color);
        [$r2, $g2, $b2] = self::rgb($with);
        $amount = max(0.0, min(1.0, $amount));

        return sprintf(
            '#%02x%02x%02x',
            (int) round($r1 + ($r2 - $r1) * $amount),
            (int) round($g1 + ($g2 - $g1) * $amount),
            (int) round($b1 + ($b2 - $b1) * $amount),
        );
    }

    public static function tint(string $color, float $amount): string
    {
        return self::mix($color, '#ffffff', $amount);
    }

    /**
     * Relative luminance (WCAG), 0 = black, 1 = white.
     */
    public static function luminance(string $color): float
    {
        $channels = array_map(function (int $channel): float {
            $c = $channel / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::rgb($color));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * Black or white, whichever reads better on $background.
     */
    public static function readableOn(string $background): string
    {
        return self::luminance($background) > 0.45 ? '#111827' : '#ffffff';
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public static function rgb(string $color): array
    {
        $hex = ltrim($color, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return [0, 0, 0];
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
