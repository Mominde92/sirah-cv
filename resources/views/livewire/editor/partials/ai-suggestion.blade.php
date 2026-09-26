@if ($aiSuggestion)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="ai-suggestion-title"
         x-data="{ selected: @js($aiSuggestion['kind'] === 'skills' ? $aiSuggestion['value'] : []) }" @keydown.escape.window="$wire.dismissAiSuggestion()">
        <div class="absolute inset-0 bg-ink-950/50" wire:click="dismissAiSuggestion"></div>
        <div class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-lift">
            <div class="flex items-start gap-3">
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600"><x-heroicon-o-sparkles class="size-5" aria-hidden="true" /></span>
                <div>
                    <h2 id="ai-suggestion-title" class="text-base font-semibold text-ink-900">{{ $aiSuggestion['kind'] === 'skills' ? __('ai.skills_title') : __('ai.suggestion_title') }}</h2>
                    <p class="mt-0.5 text-sm text-ink-500">{{ __('ai.suggestion_note') }}</p>
                </div>
            </div>

            @if ($aiSuggestion['kind'] === 'skills')
                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach ($aiSuggestion['value'] as $skill)
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-full px-3 py-1.5 text-sm ring-1 transition"
                               :class="selected.includes(@js($skill)) ? 'bg-brand-50 text-brand-800 ring-brand-300' : 'text-ink-600 ring-ink-200'">
                            <input type="checkbox" value="{{ $skill }}" x-model="selected" class="sr-only">
                            <span x-show="selected.includes(@js($skill))" aria-hidden="true"><x-heroicon-s-check class="size-3.5" /></span>
                            {{ $skill }}
                        </label>
                    @endforeach
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <x-ui.button variant="secondary" wire:click="dismissAiSuggestion">{{ __('ai.discard') }}</x-ui.button>
                    <x-ui.button x-bind:disabled="selected.length === 0" x-on:click="$wire.acceptAiSuggestion(selected)">{{ __('ai.apply_selected') }}</x-ui.button>
                </div>
            @else
                <div class="mt-5 grid gap-4 md:grid-cols-2" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
                    @if (filled($aiSuggestion['original']))
                        <div>
                            <p class="mb-1.5 text-xs font-semibold tracking-wide text-ink-500 uppercase">{{ __('ai.original') }}</p>
                            <div class="max-h-72 overflow-y-auto rounded-xl bg-ink-50 p-3 text-sm leading-6 whitespace-pre-line text-ink-600 ring-1 ring-ink-200">{{ $aiSuggestion['original'] }}</div>
                        </div>
                    @endif
                    <div @class(['md:col-span-2' => blank($aiSuggestion['original'])])>
                        <p class="mb-1.5 text-xs font-semibold tracking-wide text-violet-600 uppercase">{{ __('ai.suggested') }}</p>
                        <div class="max-h-72 overflow-y-auto rounded-xl bg-violet-50/60 p-3 text-sm leading-6 whitespace-pre-line text-ink-900 ring-1 ring-violet-200">{{ $aiSuggestion['value'] }}</div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <x-ui.button variant="secondary" wire:click="dismissAiSuggestion">{{ __('ai.discard') }}</x-ui.button>
                    <x-ui.button wire:click="acceptAiSuggestion">{{ __('ai.apply') }}</x-ui.button>
                </div>
            @endif
        </div>
    </div>
@endif
