<section id="how-it-works" class="scroll-mt-20 py-24">
    <div class="container-page">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold text-brand-600">{{ __('landing.how.eyebrow') }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-balance text-ink-900 sm:text-4xl">{{ __('landing.how.title') }}</h2>
        </div>

        <ol class="relative mt-16 grid gap-10 md:grid-cols-4 md:gap-6">
            <div class="absolute inset-x-[12%] top-6 hidden h-px bg-gradient-to-r from-transparent via-ink-200 to-transparent md:block" aria-hidden="true"></div>
            @foreach (['choose', 'fill', 'design', 'download'] as $index => $step)
                <li class="relative text-center">
                    <span class="relative mx-auto flex size-12 items-center justify-center rounded-full bg-white text-base font-semibold text-brand-600 shadow-soft ring-1 ring-ink-200">
                        {{ $index + 1 }}
                    </span>
                    <h3 class="mt-6 text-base font-semibold text-ink-900">{{ __('landing.how.steps.'.$step.'.title') }}</h3>
                    <p class="mx-auto mt-2 max-w-xs text-sm leading-6 text-ink-600">{{ __('landing.how.steps.'.$step.'.body') }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>
