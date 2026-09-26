<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Enums\SectionType;
use Illuminate\Support\Str;

/**
 * Field definitions for every section type. The editor form, validation rules,
 * sanitisation and exporters all read from here, so a new section type is one
 * entry in this map plus one Blade partial.
 *
 * Field types: text, textarea, month, checkbox, url, email, level, select, list.
 */
final class SectionSchema
{
    public const MAX_ITEMS = 50;

    private const LANGUAGE_LEVELS = ['native', 'fluent', 'professional', 'intermediate', 'basic'];

    /**
     * @return array<string, array{type: string, max?: int, options?: list<string>, wide?: bool}>
     */
    public function fields(SectionType $type): array
    {
        return match ($type) {
            SectionType::Summary => [
                'content' => ['type' => 'textarea', 'max' => 3000, 'wide' => true],
            ],
            SectionType::Experience => [
                'position' => ['type' => 'text', 'max' => 150],
                'employer' => ['type' => 'text', 'max' => 150],
                'location' => ['type' => 'text', 'max' => 120],
                'start_date' => ['type' => 'month'],
                'end_date' => ['type' => 'month'],
                'is_current' => ['type' => 'checkbox'],
                'description' => ['type' => 'textarea', 'max' => 3000, 'wide' => true],
                'highlights' => ['type' => 'list', 'max' => 400, 'wide' => true],
            ],
            SectionType::Education => [
                'degree' => ['type' => 'text', 'max' => 150],
                'field' => ['type' => 'text', 'max' => 150],
                'institution' => ['type' => 'text', 'max' => 150],
                'location' => ['type' => 'text', 'max' => 120],
                'start_date' => ['type' => 'month'],
                'end_date' => ['type' => 'month'],
                'grade' => ['type' => 'text', 'max' => 60],
                'description' => ['type' => 'textarea', 'max' => 2000, 'wide' => true],
            ],
            SectionType::Skills => [
                'name' => ['type' => 'text', 'max' => 80],
                'level' => ['type' => 'level'],
                'group' => ['type' => 'text', 'max' => 80],
            ],
            SectionType::Languages => [
                'name' => ['type' => 'text', 'max' => 80],
                'proficiency' => ['type' => 'select', 'options' => self::LANGUAGE_LEVELS],
            ],
            SectionType::Certifications => [
                'name' => ['type' => 'text', 'max' => 150],
                'issuer' => ['type' => 'text', 'max' => 150],
                'issued_at' => ['type' => 'month'],
                'expires_at' => ['type' => 'month'],
                'credential_id' => ['type' => 'text', 'max' => 100],
                'url' => ['type' => 'url'],
            ],
            SectionType::Projects => [
                'name' => ['type' => 'text', 'max' => 150],
                'role' => ['type' => 'text', 'max' => 150],
                'url' => ['type' => 'url'],
                'start_date' => ['type' => 'month'],
                'end_date' => ['type' => 'month'],
                'description' => ['type' => 'textarea', 'max' => 2000, 'wide' => true],
                'highlights' => ['type' => 'list', 'max' => 400, 'wide' => true],
            ],
            SectionType::Achievements, SectionType::Awards => [
                'title' => ['type' => 'text', 'max' => 150],
                'issuer' => ['type' => 'text', 'max' => 150],
                'date' => ['type' => 'month'],
                'description' => ['type' => 'textarea', 'max' => 1500, 'wide' => true],
            ],
            SectionType::Volunteer => [
                'role' => ['type' => 'text', 'max' => 150],
                'organization' => ['type' => 'text', 'max' => 150],
                'location' => ['type' => 'text', 'max' => 120],
                'start_date' => ['type' => 'month'],
                'end_date' => ['type' => 'month'],
                'description' => ['type' => 'textarea', 'max' => 2000, 'wide' => true],
            ],
            SectionType::Publications => [
                'title' => ['type' => 'text', 'max' => 200],
                'publisher' => ['type' => 'text', 'max' => 150],
                'date' => ['type' => 'month'],
                'url' => ['type' => 'url'],
                'description' => ['type' => 'textarea', 'max' => 1500, 'wide' => true],
            ],
            SectionType::Courses => [
                'name' => ['type' => 'text', 'max' => 150],
                'institution' => ['type' => 'text', 'max' => 150],
                'date' => ['type' => 'month'],
                'description' => ['type' => 'textarea', 'max' => 1000, 'wide' => true],
            ],
            SectionType::References => [
                'name' => ['type' => 'text', 'max' => 120],
                'position' => ['type' => 'text', 'max' => 120],
                'company' => ['type' => 'text', 'max' => 150],
                'email' => ['type' => 'email'],
                'phone' => ['type' => 'text', 'max' => 40],
                'relationship' => ['type' => 'text', 'max' => 120],
            ],
            SectionType::Custom => [
                'title' => ['type' => 'text', 'max' => 150],
                'subtitle' => ['type' => 'text', 'max' => 150],
                'date' => ['type' => 'text', 'max' => 60],
                'url' => ['type' => 'url'],
                'description' => ['type' => 'textarea', 'max' => 2000, 'wide' => true],
            ],
        };
    }

    /**
     * Field used as the item's label in the editor list.
     */
    public function labelField(SectionType $type): string
    {
        return array_key_first($this->fields($type));
    }

    /**
     * @return array<string, mixed>
     */
    public function emptyItem(SectionType $type): array
    {
        $item = ['id' => (string) Str::uuid()];

        foreach ($this->fields($type) as $name => $field) {
            $item[$name] = match ($field['type']) {
                'checkbox' => false,
                'list' => [],
                'level' => null,
                default => '',
            };
        }

        return $item;
    }

    /**
     * Keeps only known fields, trims strings, drops control characters and enforces
     * lengths. Output is plain text: templates escape it, so no HTML survives.
     *
     * @param  array<int, mixed>  $items
     * @return list<array<string, mixed>>
     */
    public function sanitizeItems(SectionType $type, array $items): array
    {
        $fields = $this->fields($type);
        $clean = [];

        foreach (array_slice(array_values($items), 0, self::MAX_ITEMS) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $row = ['id' => $this->validId($item['id'] ?? null)];

            foreach ($fields as $name => $field) {
                $row[$name] = $this->sanitizeValue($field, $item[$name] ?? null);
            }

            $clean[] = $row;
        }

        return $clean;
    }

    /**
     * Whether an item carries any content worth rendering.
     *
     * @param  array<string, mixed>  $item
     */
    public function isFilled(SectionType $type, array $item): bool
    {
        foreach ($this->fields($type) as $name => $field) {
            $value = $item[$name] ?? null;

            if ($field['type'] === 'checkbox' || $field['type'] === 'level') {
                continue;
            }

            if (is_array($value) ? array_filter($value) !== [] : filled($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function languageLevels(): array
    {
        return self::LANGUAGE_LEVELS;
    }

    /**
     * @param  array{type: string, max?: int, options?: list<string>}  $field
     */
    private function sanitizeValue(array $field, mixed $value): mixed
    {
        return match ($field['type']) {
            'checkbox' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'level' => is_numeric($value) ? max(0, min(5, (int) $value)) : null,
            'month' => is_string($value) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) ? $value : '',
            'select' => in_array($value, $field['options'] ?? [], true) ? $value : '',
            'url' => $this->sanitizeUrl($value),
            'email' => is_string($value) && filter_var(trim($value), FILTER_VALIDATE_EMAIL) ? mb_substr(trim($value), 0, 190) : '',
            // Lists arrive as arrays (API, imports) or as one-item-per-line text (editor textarea).
            'list' => array_values(array_filter(array_map(
                fn ($line) => $this->text((string) preg_replace('/^[\s\-*•·▪–]+/u', '', (string) (is_scalar($line) ? $line : '')), $field['max'] ?? 400),
                array_slice(is_array($value) ? $value : (is_string($value) ? explode("\n", $value) : []), 0, 30),
            ), fn (string $line) => $line !== '')),
            'textarea' => $this->text($value, $field['max'] ?? 2000, multiline: true),
            default => $this->text($value, $field['max'] ?? 255),
        };
    }

    public function text(mixed $value, int $max, bool $multiline = false): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        $value = str_replace(["\r\n", "\r"], "\n", (string) $value);
        // Strip control characters except newlines/tabs in multi-line fields.
        $value = (string) preg_replace($multiline ? '/[^\P{C}\n\t]/u' : '/\p{C}/u', '', $value);
        $value = $multiline ? (string) preg_replace("/\n{3,}/", "\n\n", $value) : (string) preg_replace('/\s+/u', ' ', $value);

        return mb_substr(trim($value), 0, $max);
    }

    public function sanitizeUrl(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '';
        }

        $url = trim($value);

        if (! preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
            $url = 'https://'.$url;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) ? mb_substr($url, 0, 255) : '';
    }

    private function validId(mixed $id): string
    {
        return is_string($id) && Str::isUuid($id) ? $id : (string) Str::uuid();
    }
}
