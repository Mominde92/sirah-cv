@php
    $layout = auth()->check() ? 'layouts.app' : 'layouts.marketing';
@endphp

<x-dynamic-component :component="$layout" :title="__('templates.title')">
    <div x-data="{ filter: 'all', language: @js(app()->getLocale()) }" @class(['container-page pt-28 pb-20' => ! auth()->check()])>
        @guest
            <div class="max-w-2xl">
                <h1 class="text-3xl font-semibold tracking-tight text-ink-900 sm:text-4xl">{{ __('templates.title') }}</h1>
                <p class="mt-3 text-lg text-ink-600">{{ __('templates.subtitle') }}</p>
            </div>
        @endguest

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between @guest mt-8 @endguest">
            <div class="inline-flex rounded-xl bg-white p-1 shadow-soft ring-1 ring-ink-200" role="group" aria-label="{{ __('templates.filter') }}">
                @foreach (['all' => __('templates.filters.all'), 'ats' => __('enums.template_category.ats'), 'creative' => __('enums.template_category.creative')] as $key => $label)
                    <button type="button" @click="filter = @js($key)" :aria-pressed="(filter === @js($key)).toString()"
                            :class="filter === @js($key) ? 'bg-ink-900 text-white' : 'text-ink-600 hover:text-ink-900'"
                            class="focus-ring rounded-lg px-3.5 py-1.5 text-sm font-medium transition">{{ $label }}</button>
                @endforeach
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-ink-600">
                {{ __('templates.preview_in') }}
                <select x-model="language" class="rounded-lg border-0 bg-white py-1.5 ps-3 pe-8 text-sm ring-1 ring-ink-200 focus:ring-2 focus:ring-brand-500">
                    <option value="en">English</option>
                    <option value="ar">العربية</option>
                </select>
            </label>
        </div>

        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($templates as $template)
                @php($locked = $template->is_premium && ! $canUsePremium)
                <article x-show="filter === 'all' || filter === @js($template->category->value)" class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-soft ring-1 ring-ink-200/70">
                    <div class="relative bg-ink-50 p-4">
                        @php($base = route('templates.preview', ['template' => $template, 'thumb' => 1]))
                        <x-resume.thumbnail :src="$base.'&lang='.app()->getLocale()"
                            x-effect="const url = @js($base) + '&lang=' + language, frame = $el.querySelector('iframe'); if (frame.getAttribute('src') !== url) frame.setAttribute('src', url)"
                            :label="$template->name" class="rounded-md shadow-soft" />
                        @if ($template->is_premium)
                            <span class="absolute top-6 end-6 inline-flex items-center gap-1 rounded-full bg-ink-900 px-2.5 py-1 text-xs font-semibold text-white shadow">
                                <x-heroicon-s-star class="size-3" aria-hidden="true" /> {{ __('templates.premium') }}
                            </span>
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col p-4">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold text-ink-900">{{ $template->name }}</h2>
                            <x-template.badge :status="$template->ats_status" />
                        </div>
                        <p class="mt-2 flex-1 text-xs leading-5 text-ink-500">{{ $template->description }}</p>
                        <x-ui.button :href="auth()->check() ? ($locked ? route('billing.index') : route('resumes.create', ['template' => $template->slug])) : route('register')"
                                     :variant="$locked ? 'secondary' : 'primary'" size="sm" class="mt-4 w-full">
                            {{ $locked ? __('templates.unlock') : __('templates.use') }}
                        </x-ui.button>
                    </div>
                </article>
            @endforeach
        </div>

        <section class="mt-12 grid gap-4 md:grid-cols-3">
            @foreach (\App\Enums\AtsStatus::cases() as $status)
                <div class="rounded-2xl bg-white p-5 ring-1 ring-ink-200/70">
                    <x-template.badge :status="$status" />
                    <p class="mt-3 text-sm leading-6 text-ink-600">{{ $status->description() }}</p>
                </div>
            @endforeach
        </section>
    </div>
</x-dynamic-component>
