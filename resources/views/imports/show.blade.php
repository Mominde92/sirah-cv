<x-layouts.app :title="__('imports.review.title')">
    @if ($processing)
        <x-slot:head><meta http-equiv="refresh" content="3"></x-slot:head>
        <x-ui.card class="mx-auto max-w-xl text-center">
            <span class="mx-auto inline-flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600"><x-heroicon-o-arrow-path class="size-7 animate-spin" aria-hidden="true" /></span>
            <h2 class="mt-5 text-lg font-semibold text-ink-900">{{ __('imports.processing.title') }}</h2>
            <p class="mt-2 text-sm text-ink-500">{{ __('imports.processing.body', ['file' => $import->original_name]) }}</p>
        </x-ui.card>
    @elseif ($import->status === \App\Enums\ProcessingStatus::Failed)
        <x-ui.card class="mx-auto max-w-xl text-center">
            <span class="mx-auto inline-flex size-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600"><x-heroicon-o-exclamation-triangle class="size-7" aria-hidden="true" /></span>
            <h2 class="mt-5 text-lg font-semibold text-ink-900">{{ __('imports.failed.title') }}</h2>
            <p class="mt-2 text-sm text-ink-500">{{ $import->error }}</p>
            <div class="mt-6 flex justify-center gap-3">
                <x-ui.button :href="route('imports.create')" icon="arrow-up-tray">{{ __('imports.failed.retry') }}</x-ui.button>
                <x-ui.button :href="route('resumes.create')" variant="secondary">{{ __('imports.failed.manual') }}</x-ui.button>
            </div>
        </x-ui.card>
    @elseif ($import->status === \App\Enums\ProcessingStatus::Completed && $import->resume_id)
        <x-ui.card class="mx-auto max-w-xl text-center">
            <h2 class="text-lg font-semibold text-ink-900">{{ __('imports.completed') }}</h2>
            <x-ui.button :href="route('resumes.edit', $import->resume)" class="mt-6">{{ __('resumes.actions.edit') }}</x-ui.button>
        </x-ui.card>
    @else
        @php
            $data = $import->parsed_data ?? [];
            $flags = collect($import->review_flags ?? []);
            $flaggedSections = $flags->pluck('field')->map(fn ($field) => \Illuminate\Support\Str::after($field, 'sections.'))->unique();
            $input = 'block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-ink-900 ring-1 ring-inset ring-ink-200 focus:ring-2 focus:ring-brand-500';
        @endphp

        <form method="POST" action="{{ route('imports.confirm', $import) }}" class="grid gap-6 xl:grid-cols-3">
            @csrf
            <div class="space-y-6 xl:col-span-2">
                <x-ui.alert type="info">
                    <p class="font-semibold">{{ __('imports.review.intro_title') }}</p>
                    <p class="mt-1">{{ __('imports.review.intro_body') }}</p>
                </x-ui.alert>

                @if ($flags->isNotEmpty())
                    <x-ui.card class="ring-amber-200">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-amber-800"><x-heroicon-o-flag class="size-4" aria-hidden="true" /> {{ __('imports.review.needs_review', ['count' => $flags->count()]) }}</h2>
                        <ul class="mt-3 space-y-2">
                            @foreach ($flags as $flag)
                                <li class="rounded-lg bg-amber-50/60 px-3 py-2 text-sm">
                                    <span class="font-medium text-amber-900">{{ __('imports.flags.'.$flag['reason']) }}</span>
                                    @if (filled($flag['snippet'] ?? null)) <span class="mt-0.5 block text-xs text-ink-600" dir="auto">“{{ $flag['snippet'] }}”</span> @endif
                                </li>
                            @endforeach
                        </ul>
                    </x-ui.card>
                @endif

                <x-ui.card>
                    <h2 class="text-sm font-semibold text-ink-900">{{ __('editor.personal.title') }}</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach (\App\Services\Resume\PersonalInfoSchema::FIELDS as $name => $field)
                            <div>
                                <label for="personal-{{ $name }}" class="mb-1 block text-xs font-medium text-ink-600">{{ __('editor.fields.'.$name) }}</label>
                                <input id="personal-{{ $name }}" name="personal[{{ $name }}]" value="{{ old('personal.'.$name, $data['personal'][$name] ?? '') }}" maxlength="{{ $field['max'] }}" dir="auto" class="{{ $input }}">
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>

                @foreach ($data['sections'] ?? [] as $si => $section)
                    @php($type = \App\Enums\SectionType::tryFrom($section['type']) ?? \App\Enums\SectionType::Custom)
                    <x-ui.card x-data="{ include: true }" @class(['ring-amber-200' => $flaggedSections->contains($type->value)])>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <input type="hidden" name="sections[{{ $si }}][type]" value="{{ $type->value }}">
                                <input type="hidden" name="sections[{{ $si }}][include]" :value="include ? 1 : 0">
                                <h2 class="text-sm font-semibold text-ink-900">{{ filled($section['title'] ?? null) ? $section['title'] : $type->defaultTitle($data['language'] ?? 'en') }}</h2>
                                @if ($flaggedSections->contains($type->value))
                                    <x-ui.badge color="amber"><x-heroicon-s-flag class="size-3" /> {{ __('imports.review.check') }}</x-ui.badge>
                                @endif
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm text-ink-600">
                                <input type="checkbox" x-model="include" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500"> {{ __('imports.review.include') }}
                            </label>
                        </div>
                        @if ($type === \App\Enums\SectionType::Custom)
                            <input type="hidden" name="sections[{{ $si }}][title]" value="{{ $section['title'] ?? '' }}">
                        @endif

                        <div x-show="include" class="mt-4 space-y-4">
                            @foreach ($section['items'] ?? [] as $ii => $item)
                                <div class="grid gap-3 rounded-xl bg-ink-50/70 p-3 ring-1 ring-ink-200/70 sm:grid-cols-2">
                                    <input type="hidden" name="sections[{{ $si }}][items][{{ $ii }}][id]" value="{{ $item['id'] ?? '' }}">
                                    @foreach ($schema->fields($type) as $name => $field)
                                        @php($fieldName = "sections[{$si}][items][{$ii}][{$name}]")
                                        @php($value = $item[$name] ?? '')
                                        <div @class(['sm:col-span-2' => ($field['wide'] ?? false) || $field['type'] === 'list'])>
                                            @if ($field['type'] === 'checkbox')
                                                <label class="flex items-center gap-2 pt-6 text-sm text-ink-700">
                                                    <input type="hidden" name="{{ $fieldName }}" value="0">
                                                    <input type="checkbox" name="{{ $fieldName }}" value="1" @checked($value) class="size-4 rounded border-ink-300 text-brand-600"> {{ __('editor.fields.'.$name) }}
                                                </label>
                                            @else
                                                <label class="mb-1 block text-xs font-medium text-ink-600">{{ $type === \App\Enums\SectionType::Summary ? __('editor.fields.summary') : __('editor.fields.'.$name) }}</label>
                                                @if (in_array($field['type'], ['textarea', 'list'], true))
                                                    <textarea name="{{ $fieldName }}" rows="{{ $field['type'] === 'list' ? 4 : 3 }}" dir="auto" class="{{ $input }}">{{ is_array($value) ? implode("\n", $value) : $value }}</textarea>
                                                @elseif ($field['type'] === 'select')
                                                    <select name="{{ $fieldName }}" class="{{ $input }} pe-8">
                                                        <option value="">—</option>
                                                        @foreach ($field['options'] as $option) <option value="{{ $option }}" @selected($value === $option)>{{ __('resume.proficiency.'.$option) }}</option> @endforeach
                                                    </select>
                                                @elseif ($field['type'] === 'level')
                                                    <select name="{{ $fieldName }}" class="{{ $input }} pe-8">
                                                        <option value="">{{ __('editor.level_none') }}</option>
                                                        @for ($level = 1; $level <= 5; $level++) <option value="{{ $level }}" @selected((int) $value === $level)>{{ __('editor.levels.'.$level) }}</option> @endfor
                                                    </select>
                                                @else
                                                    <input name="{{ $fieldName }}" type="{{ $field['type'] === 'month' ? 'month' : 'text' }}" value="{{ $value }}" dir="auto" class="{{ $input }}">
                                                @endif
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                @endforeach
            </div>

            <div class="space-y-6">
                <x-ui.card class="xl:sticky xl:top-24">
                    <h2 class="text-sm font-semibold text-ink-900">{{ __('imports.review.create') }}</h2>
                    <div class="mt-4 space-y-4">
                        <x-ui.input name="title" :label="__('resumes.fields.title')" :value="pathinfo($import->original_name, PATHINFO_FILENAME)" maxlength="150" required />
                        <x-ui.select name="language" :label="__('resumes.fields.language')" :options="collect($locales)->map(fn ($l) => $l['native'])->all()" :selected="$data['language'] ?? 'en'" />
                        <x-ui.select name="template_id" :label="__('resumes.create.choose_template')" :options="$templates->mapWithKeys(fn ($t) => [$t->id => $t->name.' · '.$t->ats_status->label()])->all()" :selected="$templates->first()?->id" />
                        @error('template_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                        @unless ($resumesAllowance->allows())
                            <x-ui.alert type="warning">{{ __('billing.limit_reached', ['feature' => \App\Enums\Feature::Resumes->label(), 'limit' => $resumesAllowance->limit]) }}</x-ui.alert>
                        @endunless

                        <x-ui.button type="submit" class="w-full" icon="check" :disabled="! $resumesAllowance->allows()">{{ __('imports.review.submit') }}</x-ui.button>
                    </div>
                </x-ui.card>

                <x-ui.card x-data="{ open: false }">
                    <button type="button" @click="open = ! open" class="flex w-full items-center justify-between text-sm font-semibold text-ink-900">
                        {{ __('imports.review.original_text') }} <x-heroicon-o-chevron-down class="size-4 transition" x-bind:class="open && 'rotate-180'" />
                    </button>
                    <p class="mt-1 text-xs text-ink-500">{{ __('imports.review.original_hint') }}</p>
                    <pre x-show="open" x-collapse dir="auto" class="mt-4 max-h-[32rem] overflow-auto rounded-lg bg-ink-50 p-3 font-sans text-xs leading-5 whitespace-pre-wrap text-ink-700">{{ $import->extracted_text }}</pre>
                </x-ui.card>
            </div>
        </form>

        <form method="POST" action="{{ route('imports.destroy', $import) }}" class="mt-6">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-ink-500 underline-offset-2 hover:text-red-600 hover:underline">{{ __('imports.review.discard') }}</button>
        </form>
    @endif
</x-layouts.app>
