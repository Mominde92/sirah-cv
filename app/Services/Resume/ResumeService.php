<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Enums\Feature;
use App\Enums\ResumeSource;
use App\Enums\SectionType;
use App\Exceptions\FeatureLimitReached;
use App\Exceptions\TemplateNotAvailable;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ResumeTemplate;
use App\Models\User;
use App\Services\Templates\TemplateRegistry;
use App\Services\Usage\FeatureGate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Lifecycle of resumes: create, rename, duplicate, change template, delete.
 * Content editing (sections, styling, autosave) lives in ResumeEditorService.
 */
final class ResumeService
{
    public function __construct(
        private readonly FeatureGate $gate,
        private readonly TemplateRegistry $templates,
        private readonly SectionSchema $schema,
        private readonly PersonalInfoSchema $personal,
    ) {
    }

    /**
     * @param  array<string, mixed>|null  $personal  Pre-filled personal info (e.g. from an import).
     * @param  list<array{type: string, title?: ?string, items?: array<int, mixed>}>|null  $sections
     * @param  array<string, mixed>|null  $styling
     *
     * @throws FeatureLimitReached|TemplateNotAvailable
     */
    public function create(
        User $user,
        string $title,
        ResumeTemplate $template,
        string $language,
        ResumeSource $source = ResumeSource::Scratch,
        ?array $personal = null,
        ?array $sections = null,
        ?array $styling = null,
    ): Resume {
        $this->gate->authorize($user, Feature::Resumes);
        $this->assertTemplateUsable($user, $template, $language);

        return DB::transaction(function () use ($user, $title, $template, $language, $source, $personal, $sections, $styling): Resume {
            $resume = $user->resumes()->create([
                'template_id' => $template->id,
                'title' => $this->cleanTitle($title),
                'language' => $language,
                'source' => $source,
                'data' => ['personal' => $this->personal->sanitize($personal ?? $this->personalFromUser($user))],
                'styling' => $styling,
                'last_edited_at' => now(),
            ]);

            $this->createSections($resume, $sections ?? $this->defaultSections());

            return $resume->load('sections', 'template');
        });
    }

    public function rename(Resume $resume, string $title): Resume
    {
        $resume->forceFill(['title' => $this->cleanTitle($title)])->save();

        return $resume;
    }

    /**
     * @throws FeatureLimitReached|TemplateNotAvailable
     */
    public function duplicate(Resume $resume, User $user): Resume
    {
        $this->gate->authorize($user, Feature::Resumes);
        $resume->loadMissing(['sections', 'template']);

        // Copying from a premium template the user can no longer use falls back to the default template.
        $template = $this->templates->isAvailableTo($user, $resume->template) ? $resume->template : $this->templates->default();

        return DB::transaction(function () use ($resume, $user, $template): Resume {
            $copy = $user->resumes()->create([
                'template_id' => $template->id,
                'parent_id' => $resume->id,
                'title' => $this->cleanTitle(__('resumes.copy_of', ['title' => $resume->title])),
                'language' => $resume->language,
                'status' => $resume->status,
                'source' => ResumeSource::Duplicate,
                'data' => $this->copyData($resume, $user),
                'styling' => $resume->styling,
                'last_edited_at' => now(),
            ]);

            foreach ($resume->sections as $section) {
                $copy->sections()->create([
                    'type' => $section->type,
                    'title' => $section->title,
                    'position' => $section->position,
                    'is_visible' => $section->is_visible,
                    'column' => $section->column,
                    // Fresh item ids so the copy never shares identity with the original.
                    'items' => array_map(fn (array $item): array => ['id' => (string) Str::uuid()] + $item, $section->items),
                    'settings' => $section->settings,
                ]);
            }

            return $copy->load('sections', 'template');
        });
    }

    /**
     * @throws TemplateNotAvailable
     */
    public function changeTemplate(Resume $resume, User $user, ResumeTemplate $template): Resume
    {
        $this->assertTemplateUsable($user, $template, $resume->language);

        $resume->forceFill(['template_id' => $template->id, 'last_edited_at' => now()])->save();

        return $resume->setRelation('template', $template);
    }

    public function delete(Resume $resume): void
    {
        $resume->delete();
    }

    /**
     * Removes a soft-deleted resume for good, including its stored photo.
     */
    public function purge(Resume $resume): void
    {
        $photo = $resume->data['personal']['photo_path'] ?? '';

        if (is_string($photo) && $photo !== '') {
            Storage::disk(config('cv.disks.private', 'local'))->delete($photo);
        }

        $resume->forceDelete();
    }

    /**
     * @throws TemplateNotAvailable
     */
    public function assertTemplateUsable(User $user, ResumeTemplate $template, string $language): void
    {
        if (! $template->is_active || $template->trashed()) {
            throw TemplateNotAvailable::inactive();
        }

        if (! $this->templates->isAvailableTo($user, $template)) {
            throw TemplateNotAvailable::premium();
        }

        if (! $template->supportsLanguage($language)) {
            throw TemplateNotAvailable::language();
        }
    }

    /**
     * @return list<array{type: string, items: list<array<string, mixed>>}>
     */
    public function defaultSections(): array
    {
        return array_map(fn (SectionType $type): array => [
            'type' => $type->value,
            'items' => in_array($type, [SectionType::Summary, SectionType::Experience, SectionType::Education], true)
                ? [$this->schema->emptyItem($type)]
                : [],
        ], SectionType::defaults());
    }

    /**
     * @param  list<array{type: string, title?: ?string, items?: array<int, mixed>, is_visible?: bool, column?: ?string}>  $sections
     */
    private function createSections(Resume $resume, array $sections): void
    {
        $position = 0;

        foreach ($sections as $section) {
            $type = SectionType::tryFrom((string) ($section['type'] ?? ''));

            if ($type === null) {
                continue;
            }

            $resume->sections()->create([
                'type' => $type,
                'title' => filled($section['title'] ?? null) ? $this->schema->text($section['title'], 120) : null,
                'position' => $position++,
                'is_visible' => (bool) ($section['is_visible'] ?? true),
                'column' => in_array($section['column'] ?? null, ['main', 'sidebar'], true) ? $section['column'] : null,
                'items' => $this->schema->sanitizeItems($type, (array) ($section['items'] ?? [])),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function copyData(Resume $resume, User $user): array
    {
        $data = $resume->data;
        $photo = $data['personal']['photo_path'] ?? '';

        // The copy gets its own photo file, so deleting one resume never breaks the other.
        if (is_string($photo) && $photo !== '') {
            $disk = Storage::disk(config('cv.disks.private', 'local'));
            $newPath = 'resume-photos/'.$user->id.'/'.Str::uuid().'.'.pathinfo($photo, PATHINFO_EXTENSION);
            $data['personal']['photo_path'] = $disk->exists($photo) && $disk->copy($photo, $newPath) ? $newPath : '';
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    private function personalFromUser(User $user): array
    {
        $parts = preg_split('/\s+/u', trim($user->name), 2) ?: [''];

        return [
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
            'email' => $user->email,
        ];
    }

    private function cleanTitle(string $title): string
    {
        $title = $this->schema->text($title, 150);

        return $title !== '' ? $title : __('resumes.untitled');
    }
}
