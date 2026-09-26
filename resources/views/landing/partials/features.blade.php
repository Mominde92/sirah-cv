@php
    $features = [
        'live_preview' => 'eye',
        'ats' => 'shield-check',
        'export' => 'arrow-down-tray',
        'bilingual' => 'language',
        'import' => 'cloud-arrow-up',
        'design_match' => 'swatch',
        'ai' => 'sparkles',
        'job_match' => 'briefcase',
        'versions' => 'document-duplicate',
    ];
@endphp

<section id="features" class="scroll-mt-20 py-24">
    <div class="container-page">
        <div class="max-w-2xl">
            <p class="text-sm font-semibold text-brand-600">{{ __('landing.features.eyebrow') }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-balance text-ink-900 sm:text-4xl">{{ __('landing.features.title') }}</h2>
            <p class="mt-4 text-lg leading-8 text-ink-600">{{ __('landing.features.subtitle') }}</p>
        </div>

        <dl class="mt-14 grid gap-x-10 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($features as $key => $icon)
                <div>
                    <dt>
                        <span class="inline-flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                            <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5" aria-hidden="true" />
                        </span>
                        <p class="mt-5 text-base font-semibold text-ink-900">{{ __('landing.features.items.'.$key.'.title') }}</p>
                    </dt>
                    <dd class="mt-2 text-sm leading-6 text-ink-600">{{ __('landing.features.items.'.$key.'.body') }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>
