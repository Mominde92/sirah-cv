<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Enums\Feature;
use App\Enums\SectionType;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ResumeVersion;
use App\Models\User;
use App\Services\Usage\FeatureGate;
use Illuminate\Support\Facades\DB;

/**
 * Named snapshots of a resume that can be restored later (plan feature: version_history).
 */
final class ResumeVersioning
{
    public const KEEP = 30;

    public function __construct(
        private readonly FeatureGate $gate,
        private readonly SectionSchema $schema,
    ) {
    }

    public function snapshot(Resume $resume, User $user, ?string $label = null): ResumeVersion
    {
        $this->gate->authorize($user, Feature::VersionHistory);
        $resume->load('sections');

        $version = $resume->versions()->create([
            'version' => (int) $resume->versions()->max('version') + 1,
            'label' => $label !== null ? $this->schema->text($label, 120) : null,
            'snapshot' => [
                'template_id' => $resume->template_id,
                'title' => $resume->title,
                'data' => $resume->data,
                'styling' => $resume->styling,
                'sections' => $resume->sections->map(fn (ResumeSection $section): array => [
                    'type' => $section->type->value,
                    'title' => $section->title,
                    'is_visible' => $section->is_visible,
                    'column' => $section->column,
                    'items' => $section->items,
                ])->all(),
            ],
        ]);

        // Keep the most recent snapshots only.
        $resume->versions()->where('version', '<=', $version->version - self::KEEP)->delete();

        return $version;
    }

    public function restore(Resume $resume, User $user, ResumeVersion $version): Resume
    {
        $this->gate->authorize($user, Feature::VersionHistory);
        abort_unless($version->resume_id === $resume->id, 404);

        $snapshot = $version->snapshot;

        return DB::transaction(function () use ($resume, $snapshot): Resume {
            $data = (array) ($snapshot['data'] ?? []);
            // The photo file may have been replaced since; keep the current one.
            $data['personal']['photo_path'] = $resume->data['personal']['photo_path'] ?? '';

            $resume->forceFill([
                'data' => $data,
                'styling' => $snapshot['styling'] ?? null,
                'version' => $resume->version + 1,
                'last_edited_at' => now(),
            ])->save();

            $resume->sections()->delete();

            foreach (array_values((array) ($snapshot['sections'] ?? [])) as $position => $section) {
                $type = SectionType::tryFrom((string) ($section['type'] ?? ''));

                if ($type !== null) {
                    $resume->sections()->create([
                        'type' => $type,
                        'title' => $section['title'] ?? null,
                        'position' => $position,
                        'is_visible' => (bool) ($section['is_visible'] ?? true),
                        'column' => $section['column'] ?? null,
                        'items' => $this->schema->sanitizeItems($type, (array) ($section['items'] ?? [])),
                    ]);
                }
            }

            return $resume->fresh(['sections', 'template']);
        });
    }
}
