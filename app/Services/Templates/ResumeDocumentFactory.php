<?php

declare(strict_types=1);

namespace App\Services\Templates;

use App\Data\DocumentSection;
use App\Data\ResumeDocument;
use App\Data\TemplateConfiguration;
use App\Enums\SectionType;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ResumeTemplate;
use App\Services\Localization\LocaleService;
use App\Services\Resume\PersonalInfoSchema;
use App\Services\Resume\SectionSchema;
use Illuminate\Support\Facades\Storage;

/**
 * Builds a ResumeDocument from a stored resume, or from raw arrays (template
 * gallery samples, editor state before it is saved).
 */
final class ResumeDocumentFactory
{
    private const MAX_PHOTO_BYTES = 2 * 1024 * 1024;

    public function __construct(
        private readonly StyleResolver $styles,
        private readonly SectionSchema $schema,
        private readonly PersonalInfoSchema $personalSchema,
        private readonly LocaleService $locales,
    ) {
    }

    public function fromResume(Resume $resume): ResumeDocument
    {
        $resume->loadMissing(['template', 'sections']);

        return $this->make(
            template: $resume->template,
            language: $resume->language,
            personal: (array) ($resume->data['personal'] ?? []),
            sections: $resume->sections->map(fn (ResumeSection $section): array => [
                'type' => $section->type->value,
                'title' => $section->title,
                'is_visible' => $section->is_visible,
                'column' => $section->column,
                'items' => $section->items,
            ])->all(),
            styling: $resume->styling,
            title: $resume->title,
        );
    }

    /**
     * @param  array<string, mixed>  $personal
     * @param  list<array{type: string, title?: ?string, is_visible?: bool, column?: ?string, items?: array<int, mixed>}>  $sections
     * @param  array<string, mixed>|null  $styling
     */
    public function make(
        ResumeTemplate $template,
        string $language,
        array $personal,
        array $sections,
        ?array $styling = null,
        string $title = '',
    ): ResumeDocument {
        $config = TemplateConfiguration::fromArray($template->configuration);
        $style = $this->styles->resolve($config, $styling);
        $personal = $this->personalSchema->sanitize($personal);

        return new ResumeDocument(
            title: $title,
            language: $language,
            rtl: $this->locales->isRtl($language),
            templateSlug: $template->slug,
            config: $config,
            style: $style,
            personal: $personal,
            sections: $this->sections($sections, $config, $language),
            photoDataUri: $style->showPhoto ? $this->photo($personal['photo_path'] ?? '') : null,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return list<DocumentSection>
     */
    private function sections(array $sections, TemplateConfiguration $config, string $language): array
    {
        $documentSections = [];

        foreach ($sections as $section) {
            $type = SectionType::tryFrom((string) ($section['type'] ?? ''));

            if ($type === null || ($section['is_visible'] ?? true) === false) {
                continue;
            }

            $items = array_values(array_filter(
                $this->schema->sanitizeItems($type, (array) ($section['items'] ?? [])),
                fn (array $item): bool => $this->schema->isFilled($type, $item),
            ));

            if ($items === []) {
                continue;
            }

            $column = in_array($section['column'] ?? null, ['main', 'sidebar'], true)
                ? $section['column']
                : (in_array($type->value, $config->sidebarSections, true) ? 'sidebar' : 'main');

            $documentSections[] = new DocumentSection(
                type: $type,
                title: filled($section['title'] ?? null) ? (string) $section['title'] : $type->defaultTitle($language),
                items: $items,
                column: $column,
            );
        }

        return $documentSections;
    }

    /**
     * Inline the photo so the preview and the PDF never need a public URL for it.
     */
    private function photo(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        $disk = Storage::disk(config('cv.disks.private', 'local'));

        if (! $disk->exists($path) || $disk->size($path) > self::MAX_PHOTO_BYTES) {
            return null;
        }

        $mime = $disk->mimeType($path);

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode((string) $disk->get($path));
    }
}
