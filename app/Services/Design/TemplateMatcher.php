<?php

declare(strict_types=1);

namespace App\Services\Design;

use App\Data\TemplateConfiguration;
use App\Models\ResumeTemplate;
use App\Services\Templates\FontRegistry;
use App\Support\Color;
use Illuminate\Support\Collection;

/**
 * Picks the closest original template for an analysed design and proposes styling
 * inspired by it (colours, font category, layout). The similarity score is a
 * heuristic of structural closeness, not a promise of visual identity.
 */
final class TemplateMatcher
{
    public function __construct(private readonly FontRegistry $fonts)
    {
    }

    /**
     * @param  array<string, mixed>  $analysis
     * @param  Collection<int, ResumeTemplate>  $templates
     * @return array{template: ResumeTemplate, similarity: int, styling: array<string, mixed>}|null
     */
    public function match(array $analysis, Collection $templates): ?array
    {
        $best = null;

        foreach ($templates as $template) {
            $config = TemplateConfiguration::fromArray($template->configuration);
            $score = $this->score($analysis, $template, $config);

            if ($best === null || $score > $best['score']) {
                $best = ['template' => $template, 'config' => $config, 'score' => $score];
            }
        }

        if ($best === null) {
            return null;
        }

        return [
            'template' => $best['template'],
            'similarity' => (int) min(100, round($best['score'])),
            'styling' => $this->styling($analysis, $best['config']),
        ];
    }

    /**
     * @param  array<string, mixed>  $analysis
     */
    private function score(array $analysis, ResumeTemplate $template, TemplateConfiguration $config): float
    {
        $score = 0.0;
        $layout = $analysis['layout'] ?? 'unknown';

        // Structure dominates the score (up to 50).
        if ($layout === $config->layout) {
            $score += 50;
        } elseif ($layout !== 'single-column' && $layout !== 'unknown' && $config->layout !== 'single-column') {
            $score += 32;
        } elseif ($layout === 'unknown') {
            $score += 20;
        }

        // Header (up to 20).
        $header = $analysis['header_style'] ?? 'unknown';
        $score += match (true) {
            $header === $config->headerStyle => 20,
            $header === 'unknown' => 8,
            $header === 'classic' && in_array($config->headerStyle, ['accent', 'compact', 'split'], true) => 10,
            default => 0,
        };

        // Sidebar treatment (up to 10).
        if (($analysis['sidebar'] ?? null) !== null && $config->layout !== 'single-column') {
            $bandIsDark = Color::luminance((string) $analysis['sidebar']['color']) < 0.4;
            $score += ($bandIsDark === ($config->sidebarStyle === 'solid')) ? 10 : 4;
        }

        // Typography (up to 12).
        $typography = $analysis['typography'] ?? 'unknown';
        $templateSerif = $this->fonts->category((string) ($config->defaults['font_body'] ?? 'ibmplexsans')) === 'serif';
        $score += match (true) {
            $typography === 'unknown' => 6,
            ($typography === 'serif') === $templateSerif => 12,
            default => 0,
        };

        // Density (up to 8): compact references suit tighter templates.
        $density = $analysis['density'] ?? 'balanced';
        $spacing = (float) ($config->defaults['section_spacing'] ?? 14);
        $score += match ($density) {
            'compact' => $spacing <= 12 ? 8 : 3,
            'airy' => $spacing >= 14 ? 8 : 3,
            default => 5,
        };

        return $score;
    }

    /**
     * @param  array<string, mixed>  $analysis
     * @return array<string, mixed>
     */
    private function styling(array $analysis, TemplateConfiguration $config): array
    {
        $styling = [];
        $colors = (array) ($analysis['colors'] ?? []);

        foreach (['primary' => 'color_primary', 'accent' => 'color_accent'] as $source => $key) {
            if (is_string($colors[$source] ?? null) && preg_match('/^#[0-9a-f]{6}$/i', $colors[$source])) {
                $styling[$key] = strtolower($colors[$source]);
            }
        }

        if (is_string($colors['text'] ?? null) && Color::luminance($colors['text']) < 0.08) {
            $styling['color_text'] = strtolower($colors['text']);
        }

        if (($analysis['typography'] ?? 'unknown') !== 'unknown') {
            $serif = $analysis['typography'] === 'serif';
            $latin = $config->options('fonts', array_keys($this->fonts->options('latin')));
            $arabic = $config->options('fonts_arabic', array_keys($this->fonts->options('arabic')));
            $pick = fn (array $keys) => collect($keys)->first(fn (string $key) => ($this->fonts->category($key) === 'serif') === $serif);

            if ($latinFont = $pick($latin)) {
                $styling['font_body'] = $latinFont;
                $styling['font_heading'] = $latinFont;
            }

            if ($arabicFont = $pick($arabic)) {
                $styling['font_arabic'] = $arabicFont;
            }
        }

        $layouts = $config->options('layouts', TemplateConfiguration::LAYOUTS, [$config->layout]);

        if (in_array($analysis['layout'] ?? null, $layouts, true)) {
            $styling['layout'] = $analysis['layout'];
        }

        if (($analysis['density'] ?? null) === 'compact') {
            $styling['section_spacing'] = 10;
            $styling['item_spacing'] = 6;
        }

        return $styling;
    }
}
