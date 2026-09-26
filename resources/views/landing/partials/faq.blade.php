<section id="faq" class="scroll-mt-20 py-24">
    <div class="container-page grid gap-12 lg:grid-cols-12">
        <div class="lg:col-span-4">
            <p class="text-sm font-semibold text-brand-600">{{ __('landing.faq.eyebrow') }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-balance text-ink-900 sm:text-4xl">{{ __('landing.faq.title') }}</h2>
            <p class="mt-4 text-base leading-7 text-ink-600">{{ __('landing.faq.subtitle') }}</p>
        </div>

        <div class="divide-y divide-ink-200 border-y border-ink-200 lg:col-span-8">
            @foreach (['ats_score', 'arabic', 'word', 'import', 'ai', 'free', 'privacy'] as $question)
                <details class="group py-5 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="focus-ring flex cursor-pointer list-none items-center justify-between gap-6 rounded-lg text-start text-base font-medium text-ink-900">
                        {{ __('landing.faq.items.'.$question.'.q') }}
                        <x-heroicon-o-plus class="size-5 shrink-0 text-ink-400 transition group-open:rotate-45" aria-hidden="true" />
                    </summary>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-ink-600">{{ __('landing.faq.items.'.$question.'.a') }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
