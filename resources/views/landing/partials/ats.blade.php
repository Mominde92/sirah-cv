@php
    $statuses = [
        'ats_safe' => ['emerald', 'shield-check'],
        'ats_optimized' => ['brand', 'check-badge'],
        'creative' => ['violet', 'paint-brush'],
    ];
@endphp

<section id="ats" class="scroll-mt-20 bg-ink-950 py-24 text-white">
    <div class="container-page grid gap-16 lg:grid-cols-12">
        <div class="lg:col-span-5">
            <p class="text-sm font-semibold text-brand-300">{{ __('landing.ats.eyebrow') }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{{ __('landing.ats.title') }}</h2>
            <p class="mt-5 text-lg leading-8 text-ink-300">{{ __('landing.ats.body') }}</p>

            <ul class="mt-8 space-y-3">
                @foreach (['real_text', 'headings', 'single_column', 'no_graphics', 'typography'] as $rule)
                    <li class="flex gap-3 text-sm leading-6 text-ink-200">
                        <x-heroicon-s-check class="mt-0.5 size-5 shrink-0 text-emerald-400" aria-hidden="true" />
                        {{ __('landing.ats.rules.'.$rule) }}
                    </li>
                @endforeach
            </ul>

            <p class="mt-8 rounded-xl bg-white/5 p-4 text-sm leading-6 text-ink-300 ring-1 ring-white/10">
                <x-heroicon-o-information-circle class="me-1 inline size-4 align-[-3px] text-brand-300" aria-hidden="true" />
                {{ __('landing.ats.honesty') }}
            </p>
        </div>

        <div class="grid gap-4 lg:col-span-7">
            @foreach ($statuses as $status => [$color, $icon])
                <div class="rounded-2xl bg-white/[0.04] p-6 ring-1 ring-white/10">
                    <div class="flex items-center gap-3">
                        <span @class([
                            'inline-flex size-10 items-center justify-center rounded-xl',
                            'bg-emerald-400/10 text-emerald-300' => $color === 'emerald',
                            'bg-brand-400/15 text-brand-300' => $color === 'brand',
                            'bg-violet-400/10 text-violet-300' => $color === 'violet',
                        ])>
                            <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5" aria-hidden="true" />
                        </span>
                        <h3 class="text-lg font-semibold">{{ __('enums.ats_status.'.$status.'.label') }}</h3>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-ink-300">{{ __('enums.ats_status.'.$status.'.description') }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
