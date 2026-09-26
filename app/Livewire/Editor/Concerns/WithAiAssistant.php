<?php

declare(strict_types=1);

namespace App\Livewire\Editor\Concerns;

use App\Enums\Feature;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\FeatureLimitReached;
use App\Services\Ai\AiAssistant;
use App\Services\Resume\SectionSchema;
use App\Services\Usage\FeatureGate;
use Illuminate\Support\Str;

/**
 * AI writing help inside the editor. Every result is a suggestion shown next to
 * the original; it is written into the resume only when the user accepts it.
 *
 * @mixin \App\Livewire\Editor\ResumeEditor
 */
trait WithAiAssistant
{
    /**
     * @var array{kind: string, section: string, item: ?string, field: ?string, original: mixed, value: mixed}|null
     */
    public ?array $aiSuggestion = null;

    public function aiAvailable(): bool
    {
        return app(AiAssistant::class)->enabled();
    }

    public function aiAllowed(): bool
    {
        return app(FeatureGate::class)->allows(auth()->user(), Feature::AiRequests);
    }

    public function aiSummary(string $sectionKey, string $itemId): void
    {
        $this->aiCall(fn (AiAssistant $ai) => [
            'kind' => 'text',
            'section' => $sectionKey,
            'item' => $itemId,
            'field' => 'content',
            'original' => $this->aiItemValue($sectionKey, $itemId, 'content'),
            'value' => $ai->generateSummary(auth()->user(), $this->resume),
        ]);
    }

    public function aiImprove(string $sectionKey, string $itemId, string $field): void
    {
        $original = trim((string) $this->aiItemValue($sectionKey, $itemId, $field));

        if ($original === '') {
            $this->dispatch('notify', type: 'warning', message: __('ai.errors.empty_text'));

            return;
        }

        $section = $this->aiSection($sectionKey);

        $this->aiCall(fn (AiAssistant $ai) => [
            'kind' => 'text',
            'section' => $sectionKey,
            'item' => $itemId,
            'field' => $field,
            'original' => $original,
            'value' => $ai->improveText(auth()->user(), $this->resume, $original, str_replace('_', ' ', (string) $section['type'])),
        ]);
    }

    public function aiGrammar(string $sectionKey, string $itemId, string $field): void
    {
        $original = trim((string) $this->aiItemValue($sectionKey, $itemId, $field));

        if ($original === '') {
            return;
        }

        $this->aiCall(fn (AiAssistant $ai) => [
            'kind' => 'text',
            'section' => $sectionKey,
            'item' => $itemId,
            'field' => $field,
            'original' => $original,
            'value' => $ai->improveGrammar(auth()->user(), $this->resume, $original),
        ]);
    }

    public function aiBullets(string $sectionKey, string $itemId): void
    {
        $bullets = array_values(array_filter(array_map('trim', explode("\n", (string) $this->aiItemValue($sectionKey, $itemId, 'highlights')))));

        if ($bullets === []) {
            $this->dispatch('notify', type: 'warning', message: __('ai.errors.empty_bullets'));

            return;
        }

        $role = (string) ($this->aiItemValue($sectionKey, $itemId, 'position') ?? $this->aiItemValue($sectionKey, $itemId, 'name') ?? '');

        $this->aiCall(fn (AiAssistant $ai) => [
            'kind' => 'text',
            'section' => $sectionKey,
            'item' => $itemId,
            'field' => 'highlights',
            'original' => implode("\n", $bullets),
            'value' => implode("\n", $ai->rewriteBullets(auth()->user(), $this->resume, $role, $bullets)),
        ]);
    }

    public function aiSkills(string $sectionKey): void
    {
        $this->aiCall(fn (AiAssistant $ai) => [
            'kind' => 'skills',
            'section' => $sectionKey,
            'item' => null,
            'field' => null,
            'original' => null,
            'value' => $ai->suggestSkills(auth()->user(), $this->resume),
        ]);
    }

    /**
     * @param  list<string>|null  $selected  For skill suggestions: the names the user ticked.
     */
    public function acceptAiSuggestion(?array $selected = null): void
    {
        $suggestion = $this->aiSuggestion;
        $this->aiSuggestion = null;

        if ($suggestion === null) {
            return;
        }

        if ($suggestion['kind'] === 'skills') {
            $names = array_values(array_intersect((array) $suggestion['value'], $selected ?? (array) $suggestion['value']));
            $schema = app(SectionSchema::class);

            $this->applyToSection($suggestion['section'], function (array $section) use ($names, $schema): array {
                $existing = array_map('mb_strtolower', array_column($section['items'], 'name'));

                foreach ($names as $name) {
                    if (! in_array(mb_strtolower($name), $existing, true)) {
                        $section['items'][] = ['id' => (string) Str::uuid(), 'name' => $schema->text($name, 80), 'level' => null, 'group' => ''];
                    }
                }

                return $section;
            });

            return;
        }

        $this->applyToSection($suggestion['section'], function (array $section) use ($suggestion): array {
            foreach ($section['items'] as $index => $item) {
                if ($item['id'] === $suggestion['item']) {
                    $section['items'][$index][$suggestion['field']] = (string) $suggestion['value'];
                }
            }

            return $section;
        });
    }

    public function dismissAiSuggestion(): void
    {
        $this->aiSuggestion = null;
    }

    /**
     * @param  callable(AiAssistant): array<string, mixed>  $call
     */
    private function aiCall(callable $call): void
    {
        try {
            $suggestion = $call(app(AiAssistant::class));
        } catch (FeatureLimitReached $exception) {
            $this->dispatch('notify', type: 'warning', message: $exception->userMessage());

            return;
        } catch (AiRequestFailed $exception) {
            $this->dispatch('notify', type: 'error', message: $exception->getMessage());

            return;
        }

        if ($suggestion['value'] === '' || $suggestion['value'] === []) {
            $this->dispatch('notify', type: 'warning', message: __('ai.errors.no_suggestion'));

            return;
        }

        $this->aiSuggestion = $suggestion;
    }

    /**
     * @return array<string, mixed>
     */
    private function aiSection(string $key): array
    {
        foreach ($this->sections as $section) {
            if ($section['key'] === $key) {
                return $section;
            }
        }

        return [];
    }

    private function aiItemValue(string $sectionKey, string $itemId, string $field): mixed
    {
        foreach ($this->aiSection($sectionKey)['items'] ?? [] as $item) {
            if ($item['id'] === $itemId) {
                return $item[$field] ?? null;
            }
        }

        return null;
    }
}
