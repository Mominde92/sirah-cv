<?php

declare(strict_types=1);

namespace App\Livewire\Editor;

use App\Data\StyleSettings;
use App\Data\TemplateConfiguration;
use App\Enums\Feature;
use App\Enums\SectionType;
use App\Exceptions\FeatureLimitReached;
use App\Exceptions\StaleResume;
use App\Models\Resume;
use App\Services\Resume\PersonalInfoSchema;
use App\Services\Resume\ResumeEditorService;
use App\Services\Resume\ResumePhotoService;
use App\Services\Resume\ResumeService;
use App\Services\Resume\ResumeVersioning;
use App\Services\Resume\SectionSchema;
use App\Services\Templates\FontRegistry;
use App\Services\Templates\ResumeDocumentFactory;
use App\Services\Templates\StyleResolver;
use App\Services\Templates\TemplateRegistry;
use App\Services\Templates\TemplateRenderer;
use App\Services\Usage\FeatureGate;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The resume editor: sections on the start side, live A4 preview in the middle,
 * design settings on the end side. Every change autosaves (debounced in the view)
 * and pushes freshly rendered preview HTML to the browser.
 */
#[Layout('components.layouts.editor')]
final class ResumeEditor extends Component
{
    use AuthorizesRequests;
    use Concerns\WithAiAssistant;
    use WithFileUploads;

    /** Data properties whose changes are persisted. */
    private const PERSISTED = ['title', 'personal', 'sections', 'styling'];

    #[Locked]
    public Resume $resume;

    #[Locked]
    public int $version = 1;

    public string $title = '';

    /** @var array<string, string> */
    public array $personal = [];

    /** @var list<array<string, mixed>> */
    public array $sections = [];

    /** @var array<string, mixed> Effective styling values shown in the design panel. */
    public array $styling = [];

    public ?string $openSection = 'personal';

    public ?string $openItem = null;

    public string $saveState = 'saved';

    public string $versionLabel = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $photo = null;

    public function mount(Resume $resume): void
    {
        $this->authorize('update', $resume);

        $this->resume = $resume->load(['template', 'sections']);
        $this->loadState();
    }

    public function updated(string $property): void
    {
        if (in_array(Str::before($property, '.'), self::PERSISTED, true)) {
            $this->persist();
        }
    }

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=120,min_height=120,max_width=6000,max_height=6000']]);

        app(ResumePhotoService::class)->store($this->resume, $this->photo);
        $this->photo = null;
        $this->resume->refresh();
        $this->personal['photo_path'] = $this->resume->data['personal']['photo_path'] ?? '';

        if (! ($this->styling['show_photo'] ?? false) && $this->templateAllowsPhoto()) {
            $this->styling['show_photo'] = true;
            $this->persist();

            return;
        }

        $this->refreshPreview();
    }

    public function removePhoto(): void
    {
        app(ResumePhotoService::class)->remove($this->resume);
        $this->resume->refresh();
        $this->personal['photo_path'] = '';
        $this->refreshPreview();
    }

    /* ----------------------------------------------------------------- Sections */

    public function addSection(string $type): void
    {
        $sectionType = SectionType::tryFrom($type);

        if ($sectionType === null || (! $sectionType->allowsMultiple() && collect($this->sections)->contains('type', $type))) {
            return;
        }

        $key = 'n'.Str::random(10);
        $this->sections[] = [
            'id' => null,
            'key' => $key,
            'type' => $type,
            'title' => '',
            'is_visible' => true,
            'column' => '',
            'items' => [app(ResumeEditorService::class)->toEditorItem($sectionType, app(SectionSchema::class)->emptyItem($sectionType))],
        ];

        $this->openSection = $key;
        $this->openItem = $this->sections[array_key_last($this->sections)]['items'][0]['id'];
        $this->persist();
    }

    public function removeSection(string $key): void
    {
        $this->sections = array_values(array_filter($this->sections, fn (array $section): bool => $section['key'] !== $key));
        $this->persist();
    }

    public function toggleSection(string $key): void
    {
        $this->mutateSection($key, fn (array $section): array => ['is_visible' => ! $section['is_visible']] + $section);
    }

    /**
     * Called by the x-sort plugin with the dragged section key and its new index.
     */
    public function sortSections(string $key, int $position): void
    {
        $index = $this->sectionIndex($key);

        if ($index === null) {
            return;
        }

        $section = array_splice($this->sections, $index, 1)[0];
        array_splice($this->sections, max(0, min($position, count($this->sections))), 0, [$section]);
        $this->persist();
    }

    public function moveSection(string $key, int $direction): void
    {
        $index = $this->sectionIndex($key);

        if ($index !== null) {
            $this->sortSections($key, $index + ($direction < 0 ? -1 : 1));
        }
    }

    /* -------------------------------------------------------------------- Items */

    public function addItem(string $key): void
    {
        $index = $this->sectionIndex($key);

        if ($index === null || count($this->sections[$index]['items']) >= SectionSchema::MAX_ITEMS) {
            return;
        }

        $type = SectionType::from($this->sections[$index]['type']);
        $item = app(ResumeEditorService::class)->toEditorItem($type, app(SectionSchema::class)->emptyItem($type));
        $this->sections[$index]['items'][] = $item;
        $this->openItem = $item['id'];
        $this->persist();
    }

    public function removeItem(string $key, string $itemId): void
    {
        $this->mutateSection($key, fn (array $section): array => [
            'items' => array_values(array_filter($section['items'], fn (array $item): bool => $item['id'] !== $itemId)),
        ] + $section);
    }

    public function duplicateItem(string $key, string $itemId): void
    {
        $this->mutateSection($key, function (array $section) use ($itemId): array {
            $items = [];

            foreach ($section['items'] as $item) {
                $items[] = $item;

                if ($item['id'] === $itemId) {
                    $items[] = ['id' => (string) Str::uuid()] + $item;
                }
            }

            return ['items' => array_slice($items, 0, SectionSchema::MAX_ITEMS)] + $section;
        });
    }

    public function sortItems(string $key, string $itemId, int $position): void
    {
        $this->mutateSection($key, function (array $section) use ($itemId, $position): array {
            $items = $section['items'];
            $from = array_search($itemId, array_column($items, 'id'), true);

            if ($from === false) {
                return $section;
            }

            $item = array_splice($items, $from, 1)[0];
            array_splice($items, max(0, min($position, count($items))), 0, [$item]);

            return ['items' => $items] + $section;
        });
    }

    /* ------------------------------------------------------------------- Design */

    public function changeTemplate(int $templateId): void
    {
        $template = app(TemplateRegistry::class)->findActive($templateId);

        if ($template === null) {
            return;
        }

        try {
            app(ResumeService::class)->changeTemplate($this->resume, auth()->user(), $template);
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'warning', message: collect($exception->errors())->flatten()->first());

            return;
        }

        // A new template starts from its own design defaults.
        $this->resume->forceFill(['styling' => null])->save();
        $this->resume->setRelation('template', $template);
        $this->styling = $this->effectiveStyling();
        $this->persist();
    }

    public function resetStyling(): void
    {
        $this->resume->forceFill(['styling' => null])->save();
        $this->styling = $this->effectiveStyling();
        $this->persist();
    }

    /* ----------------------------------------------------------------- Versions */

    public function saveVersion(): void
    {
        try {
            app(ResumeVersioning::class)->snapshot($this->resume, auth()->user(), $this->versionLabel ?: null);
            $this->versionLabel = '';
            $this->dispatch('notify', type: 'success', message: __('editor.history.saved'));
        } catch (FeatureLimitReached $exception) {
            $this->dispatch('notify', type: 'warning', message: $exception->userMessage());
        }
    }

    public function restoreVersion(int $versionId): void
    {
        $version = $this->resume->versions()->whereKey($versionId)->firstOrFail();

        try {
            $this->resume = app(ResumeVersioning::class)->restore($this->resume, auth()->user(), $version);
        } catch (FeatureLimitReached $exception) {
            $this->dispatch('notify', type: 'warning', message: $exception->userMessage());

            return;
        }

        $this->loadState();
        $this->refreshPreview();
        $this->dispatch('notify', type: 'success', message: __('editor.history.restored'));
    }

    /**
     * After a conflict: discard local state and load what was saved elsewhere.
     */
    public function reloadFromServer(): void
    {
        $this->resume = $this->resume->fresh(['template', 'sections']);
        $this->loadState();
        $this->saveState = 'saved';
        $this->refreshPreview();
    }

    /* ------------------------------------------------------------------- Render */

    public function render(): View
    {
        $config = $this->templateConfig();
        $fonts = app(FontRegistry::class);
        $gate = app(FeatureGate::class);
        $user = auth()->user();

        return view('livewire.editor.resume-editor', [
            'initialPreview' => $this->previewHtml(),
            'schema' => app(SectionSchema::class),
            'personalFields' => PersonalInfoSchema::FIELDS,
            'availableSections' => $this->availableSectionTypes(),
            'templates' => app(TemplateRegistry::class)->active(),
            'canUsePremium' => $gate->allows($user, Feature::PremiumTemplates),
            'canAdvanced' => $gate->allows($user, Feature::AdvancedCustomization),
            'canVersions' => $gate->allows($user, Feature::VersionHistory),
            'versions' => $this->resume->versions()->limit(10)->get(['id', 'version', 'label', 'created_at']),
            'latinFonts' => array_intersect_key($fonts->options('latin'), array_flip($config->options('fonts', array_keys($fonts->options('latin'))))),
            'arabicFonts' => array_intersect_key($fonts->options('arabic'), array_flip($config->options('fonts_arabic', array_keys($fonts->options('arabic'))))),
            'layouts' => $config->options('layouts', TemplateConfiguration::LAYOUTS, [$config->layout]),
            'headerStyles' => $config->options('header_styles', TemplateConfiguration::HEADER_STYLES, [$config->headerStyle]),
            'titleStyles' => $config->options('section_title_styles', TemplateConfiguration::SECTION_TITLE_STYLES),
            'ranges' => [
                'font_size' => $config->range('font_size', 8.5, 12.5),
                'heading_scale' => $config->range('heading_scale', 1.05, 1.8),
                'line_height' => $config->range('line_height', 1.15, 1.8),
                'page_margin_mm' => $config->range('page_margin_mm', 10, 25),
                'section_spacing' => $config->range('section_spacing', 6, 28),
                'item_spacing' => $config->range('item_spacing', 2, 16),
            ],
            'photoAllowed' => $this->templateAllowsPhoto(),
            'rtl' => in_array($this->resume->language, config('cv.rtl_locales', []), true),
        ])->title($this->title !== '' ? $this->title : __('resumes.untitled'));
    }

    /* ---------------------------------------------------------------- Internals */

    private function persist(): void
    {
        $editor = app(ResumeEditorService::class);
        $user = auth()->user();

        try {
            $result = $editor->save(
                resume: $this->resume,
                user: $user,
                title: $this->title,
                personal: $this->personal,
                sections: $this->sections,
                styling: $this->stylingOverrides(),
                expectedVersion: $this->version,
            );
        } catch (StaleResume) {
            $this->saveState = 'conflict';

            return;
        }

        $this->version = $result['version'];
        $this->sections = $result['sections'];
        $this->resume->refresh()->load(['template', 'sections']);
        $this->saveState = 'saved';
        $this->refreshPreview();
    }

    private function refreshPreview(): void
    {
        $this->dispatch('preview-updated', html: $this->previewHtml());
    }

    private function previewHtml(): string
    {
        $document = app(ResumeDocumentFactory::class)->make(
            template: $this->resume->template,
            language: $this->resume->language,
            personal: $this->resume->data['personal'] ?? [],
            sections: $this->resume->sections->map(fn ($section): array => [
                'type' => $section->type->value,
                'title' => $section->title,
                'is_visible' => $section->is_visible,
                'column' => $section->column,
                'items' => $section->items,
            ])->all(),
            styling: $this->resume->styling,
            title: $this->resume->title,
        );

        return app(TemplateRenderer::class)->render($document);
    }

    private function loadState(): void
    {
        $this->title = $this->resume->title;
        $this->version = $this->resume->version;
        $this->personal = app(PersonalInfoSchema::class)->sanitize(
            (array) ($this->resume->data['personal'] ?? []),
            (string) ($this->resume->data['personal']['photo_path'] ?? ''),
        );
        $this->sections = app(ResumeEditorService::class)->editorSections($this->resume);
        $this->styling = $this->effectiveStyling();
    }

    /**
     * @return array<string, mixed>
     */
    private function effectiveStyling(): array
    {
        return app(StyleResolver::class)->resolve($this->templateConfig(), $this->resume->styling)->toArray();
    }

    /**
     * Only the values that differ from the template defaults are stored, so a
     * template's later design updates still reach resumes that never changed them.
     *
     * @return array<string, mixed>
     */
    private function stylingOverrides(): array
    {
        $defaults = app(StyleResolver::class)->resolve($this->templateConfig())->toArray();
        $effective = app(StyleResolver::class)->resolve($this->templateConfig(), array_intersect_key($this->styling, array_flip(StyleSettings::KEYS)))->toArray();

        return array_filter($effective, fn ($value, string $key): bool => $defaults[$key] != $value, ARRAY_FILTER_USE_BOTH);
    }

    private function templateConfig(): TemplateConfiguration
    {
        return TemplateConfiguration::fromArray($this->resume->template->configuration);
    }

    private function templateAllowsPhoto(): bool
    {
        return (bool) ($this->templateConfig()->allowed['photo'] ?? $this->templateConfig()->showPhoto);
    }

    /**
     * @return list<SectionType>
     */
    private function availableSectionTypes(): array
    {
        $used = array_column($this->sections, 'type');

        return array_values(array_filter(SectionType::cases(), fn (SectionType $type): bool => $type->allowsMultiple() || ! in_array($type->value, $used, true)));
    }

    private function sectionIndex(string $key): ?int
    {
        $index = array_search($key, array_column($this->sections, 'key'), true);

        return $index === false ? null : $index;
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $mutation
     */
    private function mutateSection(string $key, callable $mutation): void
    {
        $index = $this->sectionIndex($key);

        if ($index === null) {
            return;
        }

        $this->sections[$index] = $mutation($this->sections[$index]);
        $this->persist();
    }

    /**
     * Used by the AI trait to write an accepted suggestion into the editor state.
     *
     * @param  callable(array<string, mixed>): array<string, mixed>  $mutation
     */
    protected function applyToSection(string $key, callable $mutation): void
    {
        $this->mutateSection($key, $mutation);
    }

}
