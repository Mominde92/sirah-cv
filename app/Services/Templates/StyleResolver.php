<?php

declare(strict_types=1);

namespace App\Services\Templates;

use App\Data\StyleSettings;
use App\Data\TemplateConfiguration;

/**
 * Merges template defaults with a resume's styling overrides and clamps every
 * value to the template's allowed options. Invalid input never reaches the CSS.
 */
final class StyleResolver
{
    private const BASE = [
        'font_body' => 'ibmplexsans',
        'font_heading' => 'ibmplexsans',
        'font_arabic' => 'ibmplexsansarabic',
        'font_size' => 10.5,
        'heading_scale' => 1.3,
        'line_height' => 1.45,
        'color_primary' => '#1f3a5f',
        'color_secondary' => '#4b5563',
        'color_text' => '#111827',
        'color_accent' => '#2563eb',
        'page_margin_mm' => 16,
        'section_spacing' => 14,
        'item_spacing' => 8,
        'section_title_style' => 'underline',
    ];

    public function __construct(private readonly FontRegistry $fonts)
    {
    }

    /**
     * @param  array<string, mixed>|null  $overrides
     */
    public function resolve(TemplateConfiguration $config, ?array $overrides = null): StyleSettings
    {
        $values = array_merge(
            self::BASE,
            ['header_style' => $config->headerStyle, 'layout' => $config->layout, 'show_photo' => $config->showPhoto],
            array_intersect_key($config->defaults, array_flip(StyleSettings::KEYS)),
            array_intersect_key($overrides ?? [], array_flip(StyleSettings::KEYS)),
        );

        $latin = array_keys($this->fonts->options('latin'));
        $arabic = array_keys($this->fonts->options('arabic'));
        // Invalid overrides fall back to the template's own defaults, not the global base.
        $fallback = array_merge(self::BASE, array_intersect_key($config->defaults, self::BASE));

        return new StyleSettings(
            fontBody: $this->choice($values['font_body'], $config->options('fonts', $latin), (string) $fallback['font_body']),
            fontHeading: $this->choice($values['font_heading'], $config->options('fonts', $latin), (string) $fallback['font_heading']),
            fontArabic: $this->choice($values['font_arabic'], $config->options('fonts_arabic', $arabic), (string) $fallback['font_arabic']),
            fontSize: $this->number($values['font_size'], $config->range('font_size', 8.5, 12.5)),
            headingScale: $this->number($values['heading_scale'], $config->range('heading_scale', 1.05, 1.8)),
            lineHeight: $this->number($values['line_height'], $config->range('line_height', 1.15, 1.8)),
            colorPrimary: $this->color($values['color_primary'], (string) $fallback['color_primary']),
            colorSecondary: $this->color($values['color_secondary'], (string) $fallback['color_secondary']),
            colorText: $this->color($values['color_text'], (string) $fallback['color_text']),
            colorAccent: $this->color($values['color_accent'], (string) $fallback['color_accent']),
            pageMarginMm: $this->number($values['page_margin_mm'], $config->range('page_margin_mm', 10, 25)),
            sectionSpacing: $this->number($values['section_spacing'], $config->range('section_spacing', 6, 28)),
            itemSpacing: $this->number($values['item_spacing'], $config->range('item_spacing', 2, 16)),
            sectionTitleStyle: $this->choice($values['section_title_style'], $config->options('section_title_styles', TemplateConfiguration::SECTION_TITLE_STYLES), 'underline'),
            headerStyle: $this->choice($values['header_style'], $config->options('header_styles', TemplateConfiguration::HEADER_STYLES, [$config->headerStyle]), $config->headerStyle),
            layout: $this->choice($values['layout'], $config->options('layouts', TemplateConfiguration::LAYOUTS, [$config->layout]), $config->layout),
            showPhoto: (bool) ($config->allowed['photo'] ?? $config->showPhoto) && filter_var($values['show_photo'], FILTER_VALIDATE_BOOLEAN),
        );
    }

    /**
     * @param  list<string>  $options
     */
    private function choice(mixed $value, array $options, string $fallback): string
    {
        if (is_string($value) && in_array($value, $options, true)) {
            return $value;
        }

        return in_array($fallback, $options, true) ? $fallback : ($options[0] ?? $fallback);
    }

    /**
     * @param  array{0: float, 1: float}  $range
     */
    private function number(mixed $value, array $range): float
    {
        $number = is_numeric($value) ? (float) $value : ($range[0] + $range[1]) / 2;

        return round(max($range[0], min($range[1], $number)), 2);
    }

    private function color(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $fallback;
    }
}
