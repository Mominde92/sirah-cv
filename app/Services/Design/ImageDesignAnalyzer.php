<?php

declare(strict_types=1);

namespace App\Services\Design;

use App\Support\Color;
use RuntimeException;

/**
 * Pixel-level analysis of a resume image (JPG/PNG): colour palette, full-height
 * sidebar band, top colour banner and text density. Only structure and style
 * are measured; no text, logo or picture from the reference is ever reused.
 */
final class ImageDesignAnalyzer
{
    private const SAMPLE_WIDTH = 240;

    /**
     * @return array<string, mixed>
     */
    public function analyze(string $path): array
    {
        $source = @imagecreatefromstring((string) file_get_contents($path));

        if ($source === false) {
            throw new RuntimeException('Unsupported image.');
        }

        $image = $this->downscale($source);
        imagedestroy($source);

        $width = imagesx($image);
        $height = imagesy($image);
        $pixels = $this->pixels($image, $width, $height);
        imagedestroy($image);

        $palette = $this->palette($pixels);
        $sidebar = $this->sidebar($pixels, $width, $height);
        $banner = $this->banner($pixels, $width, $height);

        return [
            'source' => 'image',
            'layout' => $sidebar === null ? 'single-column' : ($sidebar['side'] === 'left' ? 'sidebar-left' : 'sidebar-right'),
            'sidebar' => $sidebar,
            'header_style' => $banner !== null ? 'banner' : 'classic',
            'banner' => $banner,
            'colors' => $palette,
            'density' => $this->density($pixels),
            'typography' => 'unknown',
            'aspect' => round($height / max(1, $width), 2),
        ];
    }

    /**
     * @return \GdImage
     */
    private function downscale(mixed $source): mixed
    {
        $ratio = imagesy($source) / max(1, imagesx($source));
        $height = (int) max(1, round(self::SAMPLE_WIDTH * $ratio));
        $image = imagecreatetruecolor(self::SAMPLE_WIDTH, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagecopyresampled($image, $source, 0, 0, 0, 0, self::SAMPLE_WIDTH, $height, imagesx($source), imagesy($source));

        return $image;
    }

    /**
     * @return list<list<array{0: int, 1: int, 2: int}>>
     */
    private function pixels(mixed $image, int $width, int $height): array
    {
        $rows = [];

        for ($y = 0; $y < $height; $y++) {
            $row = [];

            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $row[] = [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Dominant saturated colours become primary/accent; the darkest frequent colour is the text colour.
     *
     * @param  list<list<array{0: int, 1: int, 2: int}>>  $pixels
     * @return array{primary: string, accent: string, text: string, background: string}
     */
    private function palette(array $pixels): array
    {
        $saturated = [];
        $dark = [];
        $light = [];

        foreach ($pixels as $row) {
            foreach ($row as [$r, $g, $b]) {
                $max = max($r, $g, $b);
                $min = min($r, $g, $b);
                $key = sprintf('#%02x%02x%02x', intdiv($r, 24) * 24, intdiv($g, 24) * 24, intdiv($b, 24) * 24);

                if ($max > 235 && $min > 225) {
                    $light[$key] = ($light[$key] ?? 0) + 1;
                } elseif ($max < 90) {
                    $dark[$key] = ($dark[$key] ?? 0) + 1;
                } elseif ($max - $min > 50) {
                    $saturated[$key] = ($saturated[$key] ?? 0) + 1;
                }
            }
        }

        arsort($saturated);
        arsort($dark);
        arsort($light);

        $colors = array_keys($saturated);
        $primary = $colors[0] ?? (array_key_first($dark) ?? '#1f2533');
        $accent = $primary;

        foreach (array_slice($colors, 1) as $candidate) {
            if ($this->distance($candidate, $primary) > 90) {
                $accent = $candidate;
                break;
            }
        }

        return [
            'primary' => $this->deepen($primary),
            'accent' => $this->deepen($accent),
            'text' => array_key_first($dark) ?? '#111827',
            'background' => array_key_first($light) ?? '#ffffff',
        ];
    }

    /**
     * A column band covering most of the page height on the left or right edge.
     *
     * @param  list<list<array{0: int, 1: int, 2: int}>>  $pixels
     * @return array{side: string, width_pct: int, color: string}|null
     */
    private function sidebar(array $pixels, int $width, int $height): ?array
    {
        foreach (['left', 'right'] as $side) {
            $probeX = $side === 'left' ? (int) ($width * 0.06) : (int) ($width * 0.94);
            $filledRows = 0;
            $colors = [];

            foreach ($pixels as $row) {
                if (! $this->isWhite($row[$probeX])) {
                    $filledRows++;
                    $colors[] = $row[$probeX];
                }
            }

            if ($filledRows < $height * 0.6) {
                continue;
            }

            // Walk inwards while the column keeps the band colour.
            $band = $this->average($colors);
            $bandWidth = 0;

            for ($i = 0; $i < (int) ($width * 0.5); $i++) {
                $x = $side === 'left' ? $i : $width - 1 - $i;
                $matching = 0;

                foreach ($pixels as $row) {
                    if ($this->rgbDistance($row[$x], $band) < 60) {
                        $matching++;
                    }
                }

                if ($matching < $height * 0.45) {
                    break;
                }

                $bandWidth++;
            }

            $pct = (int) round($bandWidth / $width * 100);

            if ($pct >= 18 && $pct <= 45) {
                return ['side' => $side, 'width_pct' => $pct, 'color' => sprintf('#%02x%02x%02x', ...$band)];
            }
        }

        return null;
    }

    /**
     * A coloured band across the top of the page.
     *
     * @param  list<list<array{0: int, 1: int, 2: int}>>  $pixels
     * @return array{height_pct: int, color: string}|null
     */
    private function banner(array $pixels, int $width, int $height): ?array
    {
        $bandRows = 0;
        $colors = [];

        foreach (array_slice($pixels, 0, (int) ($height * 0.3)) as $row) {
            $filled = array_filter($row, fn (array $pixel): bool => ! $this->isWhite($pixel));

            if (count($filled) > $width * 0.8) {
                $bandRows++;
                $colors[] = $row[(int) ($width / 2)];
            } elseif ($bandRows > 0) {
                break;
            }
        }

        $pct = (int) round($bandRows / $height * 100);

        return $pct >= 5 ? ['height_pct' => $pct, 'color' => sprintf('#%02x%02x%02x', ...$this->average($colors))] : null;
    }

    /**
     * @param  list<list<array{0: int, 1: int, 2: int}>>  $pixels
     */
    private function density(array $pixels): string
    {
        $ink = 0;
        $total = 0;

        foreach ($pixels as $row) {
            foreach ($row as $pixel) {
                $total++;
                $ink += max($pixel) < 140 ? 1 : 0;
            }
        }

        $ratio = $ink / max(1, $total);

        return match (true) {
            $ratio > 0.11 => 'compact',
            $ratio < 0.05 => 'airy',
            default => 'balanced',
        };
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $pixel
     */
    private function isWhite(array $pixel): bool
    {
        return min($pixel) > 232;
    }

    /**
     * @param  list<array{0: int, 1: int, 2: int}>  $pixels
     * @return array{0: int, 1: int, 2: int}
     */
    private function average(array $pixels): array
    {
        $count = max(1, count($pixels));

        return [
            (int) (array_sum(array_column($pixels, 0)) / $count),
            (int) (array_sum(array_column($pixels, 1)) / $count),
            (int) (array_sum(array_column($pixels, 2)) / $count),
        ];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $a
     * @param  array{0: int, 1: int, 2: int}  $b
     */
    private function rgbDistance(array $a, array $b): float
    {
        return sqrt(($a[0] - $b[0]) ** 2 + ($a[1] - $b[1]) ** 2 + ($a[2] - $b[2]) ** 2);
    }

    private function distance(string $a, string $b): float
    {
        return $this->rgbDistance(Color::rgb($a), Color::rgb($b));
    }

    /**
     * Sampled colours are slightly washed out by resampling; nudge them darker for print.
     */
    private function deepen(string $color): string
    {
        return Color::luminance($color) > 0.5 ? Color::mix($color, '#000000', 0.25) : $color;
    }
}
