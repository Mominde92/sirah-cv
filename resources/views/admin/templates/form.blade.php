@php
    $exists = $template->exists;
    $input = 'block w-full rounded-xl border-0 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-ink-200 focus:ring-2 focus:ring-brand-500';
@endphp

<x-admin.shell :title="$exists ? $template->name : __('admin.templates.new')">
    <form method="POST" action="{{ $exists ? route('admin.templates.update', $template) : route('admin.templates.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($exists) @method('PUT') @endif

        <div class="space-y-6 lg:col-span-2">
            <x-ui.card>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input name="name_en" :label="__('admin.templates.name_en')" :value="$template->getTranslation('name', 'en', false)" required />
                    <x-ui.input name="name_ar" :label="__('admin.templates.name_ar')" :value="$template->getTranslation('name', 'ar', false)" dir="rtl" required />
                    <x-ui.input name="description_en" :label="__('admin.templates.description_en')" :value="$template->getTranslation('description', 'en', false)" />
                    <x-ui.input name="description_ar" :label="__('admin.templates.description_ar')" :value="$template->getTranslation('description', 'ar', false)" dir="rtl" />
                    <x-ui.input name="slug" :label="__('admin.templates.slug')" :value="$template->slug" dir="ltr" required />
                    <x-ui.input name="sort_order" type="number" :label="__('admin.templates.sort_order')" :value="$template->sort_order" required />
                    <x-ui.select name="category" :label="__('admin.templates.category')" :options="collect(\App\Enums\TemplateCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" :selected="$template->category?->value ?? $template->category" />
                    <x-ui.select name="ats_status" :label="__('admin.templates.ats_status')" :options="collect(\App\Enums\AtsStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" :selected="$template->ats_status?->value ?? $template->ats_status" />
                </div>
            </x-ui.card>

            <x-ui.card>
                <label for="configuration" class="block text-sm font-semibold text-ink-900">{{ __('admin.templates.configuration') }}</label>
                <p class="mt-1 text-xs text-ink-500">{{ __('admin.templates.configuration_hint') }}</p>
                <textarea id="configuration" name="configuration" rows="24" dir="ltr" spellcheck="false" class="{{ $input }} mt-3 font-mono text-xs leading-5">{{ old('configuration', json_encode($template->configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea>
                @error('configuration') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                @error('ats_status') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card class="space-y-4">
                <fieldset>
                    <legend class="text-sm font-medium text-ink-700">{{ __('admin.templates.languages') }}</legend>
                    <div class="mt-2 flex gap-4">
                        @foreach (config('cv.locales') as $code => $locale)
                            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="supported_languages[]" value="{{ $code }}" @checked(in_array($code, old('supported_languages', $template->supported_languages ?? []), true)) class="rounded border-ink-300 text-brand-600"> {{ $locale['native'] }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <label class="flex items-center justify-between gap-3 text-sm"><span>{{ __('admin.templates.premium_only') }}</span><input type="hidden" name="is_premium" value="0"><input type="checkbox" name="is_premium" value="1" @checked(old('is_premium', $template->is_premium)) class="rounded border-ink-300 text-brand-600"></label>
                <label class="flex items-center justify-between gap-3 text-sm"><span>{{ __('admin.templates.active') }}</span><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active)) class="rounded border-ink-300 text-brand-600"></label>
                <x-ui.button type="submit" class="w-full">{{ __('common.save') }}</x-ui.button>
            </x-ui.card>

            @if ($exists && $template->is_active)
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink-900">{{ __('admin.templates.preview') }}</h3>
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        @foreach (['en', 'ar'] as $lang)
                            <x-resume.thumbnail :src="route('templates.preview', ['template' => $template, 'thumb' => 1]).'&lang='.$lang.'&v='.$template->version" :label="$template->name" class="rounded ring-1 ring-ink-200" />
                        @endforeach
                    </div>
                </x-ui.card>
            @endif
        </div>
    </form>
</x-admin.shell>
