<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Final, validated styling for one render: template defaults merged with the
 * user's overrides and clamped to what the template allows.
 */
final readonly class StyleSettings
{
    /**
     * Keys a user may override. Keys in ADVANCED need the advanced-customization feature.
     */
    public const KEYS = [
        'font_body', 'font_heading', 'font_arabic', 'font_size', 'heading_scale', 'line_height',
        'color_primary', 'color_secondary', 'color_text', 'color_accent',
        'page_margin_mm', 'section_spacing', 'item_spacing',
        'section_title_style', 'header_style', 'layout', 'show_photo',
    ];

    public const ADVANCED = [
        'heading_scale', 'line_height', 'color_secondary', 'color_text',
        'page_margin_mm', 'section_spacing', 'item_spacing', 'header_style', 'layout',
    ];

    public function __construct(
        public string $fontBody,
        public string $fontHeading,
        public string $fontArabic,
        public float $fontSize,
        public float $headingScale,
        public float $lineHeight,
        public string $colorPrimary,
        public string $colorSecondary,
        public string $colorText,
        public string $colorAccent,
        public float $pageMarginMm,
        public float $sectionSpacing,
        public float $itemSpacing,
        public string $sectionTitleStyle,
        public string $headerStyle,
        public string $layout,
        public bool $showPhoto,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'font_body' => $this->fontBody,
            'font_heading' => $this->fontHeading,
            'font_arabic' => $this->fontArabic,
            'font_size' => $this->fontSize,
            'heading_scale' => $this->headingScale,
            'line_height' => $this->lineHeight,
            'color_primary' => $this->colorPrimary,
            'color_secondary' => $this->colorSecondary,
            'color_text' => $this->colorText,
            'color_accent' => $this->colorAccent,
            'page_margin_mm' => $this->pageMarginMm,
            'section_spacing' => $this->sectionSpacing,
            'item_spacing' => $this->itemSpacing,
            'section_title_style' => $this->sectionTitleStyle,
            'header_style' => $this->headerStyle,
            'layout' => $this->layout,
            'show_photo' => $this->showPhoto,
        ];
    }

    /**
     * Font used for body text in the given direction (Arabic fonts also cover Latin).
     */
    public function bodyFont(bool $rtl): string
    {
        return $rtl ? $this->fontArabic : $this->fontBody;
    }

    public function headingFont(bool $rtl): string
    {
        return $rtl ? $this->fontArabic : $this->fontHeading;
    }

    /**
     * Font size in points, scaled by a factor and rounded for stable CSS.
     */
    public function pt(float $factor = 1.0): string
    {
        return rtrim(rtrim(number_format($this->fontSize * $factor, 2, '.', ''), '0'), '.').'pt';
    }
}
