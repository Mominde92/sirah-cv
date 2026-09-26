<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Data\StyleSettings;
use App\Enums\Feature;
use App\Enums\SectionType;
use App\Exceptions\StaleResume;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\User;
use App\Services\Usage\FeatureGate;
use Illuminate\Support\Facades\DB;

/**
 * Persists the editor's full state in one transaction with optimistic locking:
 * the save only succeeds if nobody else saved since the editor loaded version N.
 */
final class ResumeEditorService
{
    public function __construct(
        private readonly SectionSchema $schema,
        private readonly PersonalInfoSchema $personalSchema,
        private readonly FeatureGate $gate,
    ) {
    }

    /**
     * Editor representation of a resume's sections (list fields as one-per-line text).
     *
     * @return list<array<string, mixed>>
     */
    public function editorSections(Resume $resume): array
    {
        return $resume->sections->map(fn (ResumeSection $section): array => [
            'id' => $section->id,
            'key' => 's'.$section->id,
            'type' => $section->type->value,
            'title' => $section->title ?? '',
            'is_visible' => $section->is_visible,
            'column' => $section->column ?? '',
            'items' => array_map(fn (array $item): array => $this->toEditorItem($section->type, $item), $section->items),
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function toEditorItem(SectionType $type, array $item): array
    {
        foreach ($this->schema->fields($type) as $name => $field) {
            if ($field['type'] === 'list' && is_array($item[$name] ?? null)) {
                $item[$name] = implode("\n", $item[$name]);
            }
        }

        return $item;
    }

    /**
     * Styling keys the user may change; advanced keys require the advanced-customization feature.
     *
     * @param  array<string, mixed>  $styling
     * @return array<string, mixed>
     */
    public function allowedStyling(User $user, array $styling, ?array $current = null): array
    {
        $styling = array_intersect_key($styling, array_flip(StyleSettings::KEYS));

        if (! $this->gate->allows($user, Feature::AdvancedCustomization)) {
            foreach (StyleSettings::ADVANCED as $key) {
                // Keep whatever was stored before (e.g. from a previous plan); never accept new values.
                if (array_key_exists($key, $current ?? [])) {
                    $styling[$key] = $current[$key];
                } else {
                    unset($styling[$key]);
                }
            }
        }

        return $styling;
    }

    /**
     * @param  array<string, mixed>  $personal
     * @param  list<array<string, mixed>>  $sections  Editor sections, in display order.
     * @param  array<string, mixed>  $styling
     * @return array{version: int, sections: list<array<string, mixed>>}
     *
     * @throws StaleResume
     */
    public function save(Resume $resume, User $user, string $title, array $personal, array $sections, array $styling, int $expectedVersion): array
    {
        return DB::transaction(function () use ($resume, $user, $title, $personal, $sections, $styling, $expectedVersion): array {
            $current = Resume::query()->whereKey($resume->id)->lockForUpdate()->first(['id', 'version', 'data', 'styling']);

            if ($current === null || $current->version !== $expectedVersion) {
                throw new StaleResume((int) $current?->version);
            }

            $photoPath = $current->data['personal']['photo_path'] ?? '';
            $data = $current->data;
            $data['personal'] = $this->personalSchema->sanitize($personal, is_string($photoPath) ? $photoPath : '');

            $resume->forceFill([
                'title' => $this->schema->text($title, 150) ?: __('resumes.untitled'),
                'data' => $data,
                'styling' => $this->allowedStyling($user, $styling, $current->styling) ?: null,
                'version' => $expectedVersion + 1,
                'last_edited_at' => now(),
            ])->save();

            $saved = $this->syncSections($resume, $sections);

            return ['version' => $expectedVersion + 1, 'sections' => $saved];
        });
    }

    /**
     * Stores the photo path outside the regular save so uploads never race with autosave.
     */
    public function setPhoto(Resume $resume, string $path): void
    {
        $data = $resume->data;
        $data['personal']['photo_path'] = $path;

        $resume->forceFill(['data' => $data])->save();
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>> The sections with database ids for newly created rows.
     */
    private function syncSections(Resume $resume, array $sections): array
    {
        $existing = $resume->sections()->get()->keyBy('id');
        $keep = [];
        $result = [];

        foreach (array_values($sections) as $position => $section) {
            $type = SectionType::tryFrom((string) ($section['type'] ?? ''));

            if ($type === null) {
                continue;
            }

            $attributes = [
                'type' => $type,
                'title' => $this->schema->text($section['title'] ?? '', 120) ?: null,
                'position' => $position,
                'is_visible' => (bool) ($section['is_visible'] ?? true),
                'column' => in_array($section['column'] ?? null, ['main', 'sidebar'], true) ? $section['column'] : null,
                'items' => $this->schema->sanitizeItems($type, (array) ($section['items'] ?? [])),
            ];

            $model = isset($section['id']) ? $existing->get((int) $section['id']) : null;

            if ($model !== null) {
                $model->fill($attributes);
                if ($model->isDirty()) {
                    $model->saveQuietly();
                }
            } else {
                $model = $resume->sections()->create($attributes);
            }

            $keep[] = $model->id;
            $result[] = array_merge($section, ['id' => $model->id]);
        }

        $resume->sections()->whereNotIn('id', $keep)->delete();

        return $result;
    }
}
