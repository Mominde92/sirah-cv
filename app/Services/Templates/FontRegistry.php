<?php

declare(strict_types=1);

namespace App\Services\Templates;

/**
 * Fonts available to resume templates (config/cv.php "fonts").
 */
final class FontRegistry
{
    /**
     * @return array<string, array{label: string, script: string, category: string, files: array<string, string>}>
     */
    public function all(): array
    {
        return config('cv.fonts', []);
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function label(string $key): string
    {
        return $this->all()[$key]['label'] ?? $key;
    }

    public function category(string $key): string
    {
        return $this->all()[$key]['category'] ?? 'sans';
    }

    /**
     * @return array<string, string> key => label
     */
    public function options(string $script): array
    {
        $options = [];

        foreach ($this->all() as $key => $font) {
            if ($font['script'] === $script) {
                $options[$key] = $font['label'];
            }
        }

        return $options;
    }

    public function directory(): string
    {
        return public_path('fonts');
    }

    /**
     * CSS family stack. $fallback (a Latin font) covers Latin words inside Arabic text
     * for fonts that only ship Arabic glyphs; mPDF does the same via useSubstitutions.
     */
    public function stack(string $key, ?string $fallback = null): string
    {
        $generic = $this->category($key) === 'serif' ? 'serif' : 'sans-serif';
        $fallback = $fallback !== null && $fallback !== $key ? "'{$fallback}', " : '';

        return "'{$key}', {$fallback}{$generic}";
    }

    /**
     * CSS font-face rules for the browser preview.
     *
     * @param  list<string>  $keys
     */
    public function fontFaceCss(array $keys): string
    {
        $css = '';

        foreach (array_unique($keys) as $key) {
            foreach ($this->all()[$key]['files'] ?? [] as $variant => $file) {
                $weight = $variant === 'B' ? 700 : 400;
                $style = $variant === 'I' ? 'italic' : 'normal';
                $url = asset('fonts/'.$file);
                $css .= "@font-face{font-family:'{$key}';src:url('{$url}') format('truetype');font-weight:{$weight};font-style:{$style};font-display:swap;}\n";
            }
        }

        return $css;
    }

    /**
     * mPDF "fontdata" configuration, with OpenType layout enabled so Arabic is shaped.
     *
     * @return array<string, array<string, mixed>>
     */
    public function mpdfFontData(): array
    {
        $data = [];

        foreach ($this->all() as $key => $font) {
            $entry = $font['files'];

            if ($font['script'] === 'arabic') {
                $entry['useOTL'] = 0xFF;
                $entry['useKashida'] = 75;
            }

            $data[$key] = $entry;
        }

        return $data;
    }
}
