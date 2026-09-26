<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Typed view of resume_templates.configuration. Unknown keys are ignored and
 * missing keys fall back to safe defaults, so older rows keep rendering.
 */
final readonly class TemplateConfiguration
{
    public const LAYOUTS = ['single-column', 'sidebar-left', 'sidebar-right'];

    public const HEADER_STYLES = ['classic', 'centered', 'accent', 'split', 'compact', 'banner'];

    public const SECTION_TITLE_STYLES = ['underline', 'plain', 'bar', 'caps', 'filled'];

    public const SIDEBAR_STYLES = ['tint', 'solid', 'plain'];

    /**
     * @param  array<string, mixed>  $defaults  Style tokens (see StyleSettings).
     * @param  array<string, mixed>  $allowed  Editable options and numeric ranges.
     * @param  list<string>  $sidebarSections
     */
    public function __construct(
        public string $layout,
        public string $headerStyle,
        public bool $showPhoto,
        public int $sidebarWidth,
        public string $sidebarStyle,
        public array $sidebarSections,
        public array $defaults,
        public array $allowed,
        public bool $atsMode,
    ) {
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        $layout = in_array($config['layout'] ?? null, self::LAYOUTS, true) ? $config['layout'] : 'single-column';
        $header = $config['header'] ?? [];
        $columns = $config['columns'] ?? [];

        return new self(
            layout: $layout,
            headerStyle: in_array($header['style'] ?? null, self::HEADER_STYLES, true) ? $header['style'] : 'classic',
            showPhoto: (bool) ($header['show_photo'] ?? false),
            sidebarWidth: max(24, min(40, (int) ($columns['sidebar_width'] ?? 32))),
            sidebarStyle: in_array($columns['sidebar_style'] ?? null, self::SIDEBAR_STYLES, true) ? $columns['sidebar_style'] : 'tint',
            sidebarSections: array_values(array_filter((array) ($columns['sidebar_sections'] ?? ['skills', 'languages', 'certifications', 'courses', 'references']), 'is_string')),
            defaults: (array) ($config['defaults'] ?? []),
            allowed: (array) ($config['allowed'] ?? []),
            atsMode: (bool) ($config['ats']['single_column_text_flow'] ?? false),
        );
    }

    /**
     * @return array{0: float, 1: float}
     */
    public function range(string $key, float $min, float $max): array
    {
        $range = $this->allowed[$key] ?? null;

        if (is_array($range) && count($range) === 2 && is_numeric($range[0]) && is_numeric($range[1])) {
            return [(float) $range[0], (float) $range[1]];
        }

        return [$min, $max];
    }

    /**
     * Options the template allows for $key, restricted to $universe (the values that exist).
     *
     * @param  list<string>  $universe
     * @param  list<string>|null  $whenMissing  Used when the template does not list options; defaults to $universe.
     * @return list<string>
     */
    public function options(string $key, array $universe, ?array $whenMissing = null): array
    {
        $options = $this->allowed[$key] ?? null;
        $fallback = $whenMissing ?? $universe;

        if (! is_array($options) || $options === []) {
            return $fallback;
        }

        $valid = array_values(array_intersect($options, $universe));

        return $valid !== [] ? $valid : $fallback;
    }
}
