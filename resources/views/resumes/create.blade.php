<x-layouts.app :title="__('resumes.create.title')">
    <form method="POST" action="{{ route('resumes.store') }}"
          x-data="{ template: {{ (int) old('template_id', $selected?->id) }}, language: @js(old('language', $language)) }"
          class="space-y-8" novalidate>
        @csrf

        @unless ($allowance->allows())
            <x-ui.alert type="warning">
                {{ __('billing.limit_reached', ['feature' => \App\Enums\Feature::Resumes->label(), 'limit' => $allowance->limit]) }}
                <a href="{{ route('billing.index') }}" class="ms-1 font-semibold underline underline-offset-2">{{ __('billing.see_plans') }}</a>
            </x-ui.alert>
        @endunless

        <x-ui.card>
            <div class="grid gap-5 sm:grid-cols-3">
                <x-ui.input name="title" :label="__('resumes.fields.title')" :value="__('resumes.default_title')" :hint="__('resumes.fields.title_hint')" class="sm:col-span-2" maxlength="150" required autofocus />
                <div class="space-y-1.5">
                    <label for="language" class="block text-sm font-medium text-ink-700">{{ __('resumes.fields.language') }}</label>
                    <select id="language" name="language" x-model="language" class="block w-full rounded-xl border-0 bg-white py-2.5 ps-3.5 pe-10 text-sm text-ink-900 shadow-sm ring-1 ring-inset ring-ink-200 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        @foreach ($locales as $code => $locale)
                            <option value="{{ $code }}">{{ $locale['native'] }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-ink-500">{{ __('resumes.fields.language_hint') }}</p>
                </div>
            </div>
        </x-ui.card>

        <fieldset>
            <legend class="text-base font-semibold text-ink-900">{{ __('resumes.create.choose_template') }}</legend>
            @error('template_id') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
            @error('language') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror

            <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($templates as $template)
                    @php($locked = $template->is_premium && ! $canUsePremium)
                    <label @class(['group relative block', 'cursor-pointer' => ! $locked, 'cursor-not-allowed' => $locked])>
                        <input type="radio" name="template_id" value="{{ $template->id }}" x-model.number="template" class="peer sr-only" @disabled($locked)>
                        <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-ink-200 transition peer-checked:ring-2 peer-checked:ring-brand-500 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400 group-hover:shadow-lift">
                            <div class="relative bg-ink-50 p-3">
                                @php($base = route('templates.preview', ['template' => $template, 'thumb' => 1]))
                                <x-resume.thumbnail :src="$base.'&lang='.$language"
                                    x-effect="const url = @js($base) + '&lang=' + language, frame = $el.querySelector('iframe'); if (frame.getAttribute('src') !== url) frame.setAttribute('src', url)"
                                    :label="$template->name" class="rounded-md shadow-soft" />
                                @if ($locked)
                                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-white/70 backdrop-blur-[1px]">
                                        <span class="inline-flex size-10 items-center justify-center rounded-full bg-ink-900 text-white"><x-heroicon-s-lock-closed class="size-5" aria-hidden="true" /></span>
                                        <span class="text-xs font-semibold text-ink-800">{{ __('resumes.create.premium_only') }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center justify-between gap-2 p-3">
                                <span class="truncate text-sm font-semibold text-ink-900">{{ $template->name }}</span>
                                <x-template.badge :status="$template->ats_status" class="shrink-0" />
                            </div>
                        </div>
                        <span x-cloak x-show="template === {{ $template->id }}" class="absolute -top-2 -end-2 inline-flex size-7 items-center justify-center rounded-full bg-brand-600 text-white shadow-lift">
                            <x-heroicon-s-check class="size-4" aria-hidden="true" />
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="sticky bottom-4 z-10 flex items-center justify-between gap-4 rounded-2xl bg-white/90 p-4 shadow-lift ring-1 ring-ink-200 backdrop-blur">
            <p class="text-sm text-ink-500">{{ __('resumes.create.switch_later') }}</p>
            <x-ui.button type="submit" icon-trailing="arrow-right" :disabled="! $allowance->allows()">{{ __('resumes.create.submit') }}</x-ui.button>
        </div>
    </form>
</x-layouts.app>
