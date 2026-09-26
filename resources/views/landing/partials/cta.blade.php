<section class="pb-24">
    <div class="container-page">
        <div class="relative overflow-hidden rounded-3xl bg-brand-700 px-6 py-16 text-center shadow-lift sm:px-16">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgb(139_92_246/0.55),transparent_55%),radial-gradient(circle_at_bottom_left,rgb(76_99_242/0.7),transparent_55%)]" aria-hidden="true"></div>
            <div class="relative mx-auto max-w-2xl">
                <h2 class="text-3xl font-semibold tracking-tight text-balance text-white sm:text-4xl">{{ __('landing.final_cta.title') }}</h2>
                <p class="mt-4 text-lg leading-8 text-brand-100">{{ __('landing.final_cta.body') }}</p>
                <div class="mt-9 flex justify-center">
                    <x-ui.button :href="auth()->check() ? route('dashboard') : route('register')" variant="light" size="lg" icon-trailing="arrow-right">
                        {{ __('landing.cta.start_free') }}
                    </x-ui.button>
                </div>
                <p class="mt-4 text-sm text-brand-200">{{ __('landing.final_cta.note') }}</p>
            </div>
        </div>
    </div>
</section>
