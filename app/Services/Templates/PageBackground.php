<?php

declare(strict_types=1);

namespace App\Services\Templates;

use App\Support\Color;
use RuntimeException;

/**
 * Page backgrounds for the PDF renderer. mPDF repeats an @page background on
 * every page, which gives sidebar templates a full-height colour band.
 * The browser preview draws the same band with a CSS gradient.
 */
final class PageBackground
{
    private const WIDTH_PX = 2100; // 10 px per mm on an A4 width

    /**
     * @var array<string, string>
     */
    private array $cache = [];

    public function stripe(string $color, float $widthMm, bool $onLeft): string
    {
        $key = $color.'|'.$widthMm.'|'.(int) $onLeft;

        return $this->cache[$key] ??= $this->draw($color, $widthMm, $onLeft);
    }

    private function draw(string $color, float $widthMm, bool $onLeft): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('The GD extension is required for sidebar page backgrounds.');
        }

        $image = imagecreatetruecolor(self::WIDTH_PX, 20);
        [$r, $g, $b] = Color::rgb($color);
        $white = imagecolorallocate($image, 255, 255, 255);
        $fill = imagecolorallocate($image, $r, $g, $b);

        imagefilledrectangle($image, 0, 0, self::WIDTH_PX, 20, $white);

        $stripePx = (int) round($widthMm * 10);
        $onLeft
            ? imagefilledrectangle($image, 0, 0, $stripePx, 20, $fill)
            : imagefilledrectangle($image, self::WIDTH_PX - $stripePx, 0, self::WIDTH_PX, 20, $fill);

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
