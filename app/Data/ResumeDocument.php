<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Everything a renderer or exporter needs, detached from Eloquent.
 * Templates, the PDF renderer and the DOCX builder all consume this object.
 */
final readonly class ResumeDocument
{
    /**
     * @param  array<string, string>  $personal
     * @param  list<DocumentSection>  $sections  Visible sections in display order.
     */
    public function __construct(
        public string $title,
        public string $language,
        public bool $rtl,
        public string $templateSlug,
        public TemplateConfiguration $config,
        public StyleSettings $style,
        public array $personal,
        public array $sections,
        public ?string $photoDataUri,
    ) {
    }

    public function fullName(): string
    {
        return trim(($this->personal['first_name'] ?? '').' '.($this->personal['last_name'] ?? ''));
    }

    public function hasSidebar(): bool
    {
        return $this->style->layout !== 'single-column';
    }

    /**
     * @return list<DocumentSection>
     */
    public function mainSections(): array
    {
        return array_values(array_filter($this->sections, fn (DocumentSection $s) => ! $this->hasSidebar() || $s->column !== 'sidebar'));
    }

    /**
     * @return list<DocumentSection>
     */
    public function sidebarSections(): array
    {
        return $this->hasSidebar() ? array_values(array_filter($this->sections, fn (DocumentSection $s) => $s->column === 'sidebar')) : [];
    }

    /**
     * Contact details in reading order, as plain text (ATS-safe: no icons carry meaning).
     *
     * @return list<array{key: string, text: string, href: ?string}>
     */
    public function contacts(): array
    {
        $contacts = [];

        foreach (['email', 'phone', 'location', 'website', 'linkedin', 'github', 'nationality', 'date_of_birth'] as $key) {
            $value = $this->personal[$key] ?? '';

            if ($value === '') {
                continue;
            }

            $contacts[] = [
                'key' => $key,
                'text' => in_array($key, ['website', 'linkedin', 'github'], true) ? preg_replace('#^https?://(www\.)?#i', '', rtrim($value, '/')) : $value,
                'href' => match ($key) {
                    'email' => 'mailto:'.$value,
                    'phone' => 'tel:'.preg_replace('/[^0-9+]/', '', $value),
                    'website', 'linkedin', 'github' => $value,
                    default => null,
                },
            ];
        }

        return $contacts;
    }
}
