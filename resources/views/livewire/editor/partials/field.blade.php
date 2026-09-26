{{-- One editor field, rendered from SectionSchema. Expects: $model, $id, $name, $field, $item, $type, $sectionKey. --}}
@php
    $wide = ($field['wide'] ?? false) || in_array($field['type'], ['list'], true);
    $inputClass = 'block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-ink-900 ring-1 ring-inset ring-ink-200 placeholder:text-ink-300 focus:ring-2 focus:ring-brand-500 disabled:bg-ink-50 disabled:text-ink-400';
    $isSummary = $type === \App\Enums\SectionType::Summary;
@endphp

<div @class(['col-span-2' => $wide, 'col-span-1' => ! $wide, 'flex items-end' => $field['type'] === 'checkbox'])>
    @if ($field['type'] === 'checkbox')
        <label class="flex items-center gap-2 pb-2 text-sm text-ink-700">
            <input id="{{ $id }}" type="checkbox" wire:model.live="{{ $model }}" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
            {{ __('editor.fields.'.$name) }}
        </label>
    @else
        <div class="mb-1 flex items-center justify-between gap-2">
            <label for="{{ $id }}" class="block text-xs font-medium text-ink-600">
                {{ $isSummary ? __('editor.fields.summary') : __('editor.fields.'.$name) }}
                @if ($field['type'] === 'list') <span class="font-normal text-ink-400">— {{ __('editor.one_per_line') }}</span> @endif
            </label>

            @if ($aiAvailable)
                <div class="flex items-center gap-1">
                    @if ($isSummary)
                        @include('livewire.editor.partials.ai-button', ['action' => "aiSummary('{$sectionKey}', '{$item['id']}')", 'label' => __('ai.write_summary'), 'target' => 'aiSummary', 'allowed' => $aiAllowed, 'compact' => true])
                    @endif
                    @if ($field['type'] === 'textarea')
                        @include('livewire.editor.partials.ai-button', ['action' => "aiImprove('{$sectionKey}', '{$item['id']}', '{$name}')", 'label' => __('ai.improve'), 'target' => 'aiImprove', 'allowed' => $aiAllowed, 'compact' => true])
                        @include('livewire.editor.partials.ai-button', ['action' => "aiGrammar('{$sectionKey}', '{$item['id']}', '{$name}')", 'label' => __('ai.grammar'), 'target' => 'aiGrammar', 'allowed' => $aiAllowed, 'compact' => true, 'icon' => 'check-badge'])
                    @elseif ($field['type'] === 'list')
                        @include('livewire.editor.partials.ai-button', ['action' => "aiBullets('{$sectionKey}', '{$item['id']}')", 'label' => __('ai.rewrite_bullets'), 'target' => 'aiBullets', 'allowed' => $aiAllowed, 'compact' => true])
                    @endif
                </div>
            @endif
        </div>

        @switch($field['type'])
            @case('textarea')
                <textarea id="{{ $id }}" wire:model.live.debounce.900ms="{{ $model }}" rows="{{ $isSummary ? 5 : 3 }}" maxlength="{{ $field['max'] ?? 2000 }}"
                          placeholder="{{ __('editor.placeholders.'.($isSummary ? 'summary' : 'description')) }}" dir="auto" class="{{ $inputClass }} resize-y"></textarea>
                @break
            @case('list')
                <textarea id="{{ $id }}" wire:model.live.debounce.900ms="{{ $model }}" rows="4"
                          placeholder="{{ __('editor.placeholders.highlights') }}" dir="auto" class="{{ $inputClass }} resize-y"></textarea>
                @break
            @case('month')
                <input id="{{ $id }}" type="month" wire:model.live.debounce.600ms="{{ $model }}" placeholder="YYYY-MM" dir="ltr"
                       @if ($name === 'end_date' && ($item['is_current'] ?? false)) disabled @endif
                       class="{{ $inputClass }}">
                @break
            @case('level')
                <select id="{{ $id }}" wire:model.live="{{ $model }}" class="{{ $inputClass }} pe-8">
                    <option value="">{{ __('editor.level_none') }}</option>
                    @for ($level = 1; $level <= 5; $level++)
                        <option value="{{ $level }}">{{ __('editor.levels.'.$level) }}</option>
                    @endfor
                </select>
                @break
            @case('select')
                <select id="{{ $id }}" wire:model.live="{{ $model }}" class="{{ $inputClass }} pe-8">
                    <option value="">—</option>
                    @foreach ($field['options'] as $option)
                        <option value="{{ $option }}">{{ __('resume.proficiency.'.$option) }}</option>
                    @endforeach
                </select>
                @break
            @default
                <input id="{{ $id }}" type="{{ $field['type'] === 'url' ? 'url' : ($field['type'] === 'email' ? 'email' : 'text') }}"
                       wire:model.live.debounce.700ms="{{ $model }}" maxlength="{{ $field['max'] ?? 255 }}"
                       @if (in_array($field['type'], ['url', 'email'], true) || $name === 'phone') dir="ltr" @else dir="auto" @endif
                       placeholder="{{ __('editor.placeholders.'.$name) }}" class="{{ $inputClass }}">
        @endswitch
    @endif
</div>
