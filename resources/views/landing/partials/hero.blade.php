<section class="relative overflow-hidden pt-28 pb-20 sm:pt-36 lg:pb-28">
    <div class="pointer-events-none absolute inset-x-0 -top-40 -z-10 h-[640px] bg-[radial-gradient(ellipse_at_top,var(--color-brand-100),transparent_65%)]" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(to_right,rgb(16_24_40/0.035)_1px,transparent_1px),linear-gradient(to_bottom,rgb(16_24_40/0.035)_1px,transparent_1px)] bg-[size:56px_56px] [mask-image:radial-gradient(ellipse_at_top,black,transparent_70%)]" aria-hidden="true"></div>

    <div class="container-page grid items-center gap-16 lg:grid-cols-12">
        <div class="lg:col-span-6">
            <x-ui.badge color="brand" class="px-3 py-1">
                <x-heroicon-s-sparkles class="size-3.5" aria-hidden="true" />
                {{ __('landing.hero.eyebrow') }}
            </x-ui.badge>

            <h1 class="mt-6 text-4xl font-semibold tracking-tight text-balance text-ink-900 sm:text-5xl lg:text-6xl lg:leading-[1.08]">
                {{ __('landing.hero.title') }}
                <span class="bg-gradient-to-r from-brand-600 to-violet-600 bg-clip-text text-transparent">{{ __('landing.hero.title_highlight') }}</span>
            </h1>

            <p class="mt-6 max-w-xl text-lg leading-8 text-pretty text-ink-600">{{ __('landing.hero.subtitle') }}</p>

            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                <x-ui.button :href="auth()->check() ? route('dashboard') : route('register')" size="lg" icon-trailing="arrow-right">
                    {{ __('landing.cta.start_free') }}
                </x-ui.button>
                <x-ui.button href="#templates" variant="secondary" size="lg">{{ __('landing.hero.browse_templates') }}</x-ui.button>
            </div>

            <ul class="mt-10 grid grid-cols-1 gap-3 text-sm text-ink-600 sm:grid-cols-3">
                @foreach (['ats', 'bilingual', 'formats'] as $point)
                    <li class="flex items-center gap-2">
                        <x-heroicon-s-check-circle class="size-5 shrink-0 text-emerald-500" aria-hidden="true" />
                        {{ __('landing.hero.points.'.$point) }}
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="relative lg:col-span-6" aria-hidden="true">
            <div class="relative mx-auto max-w-lg">
                <div class="absolute -inset-6 -z-10 rounded-[2rem] bg-gradient-to-br from-brand-200/60 via-violet-100/40 to-transparent blur-2xl"></div>

                {{-- Editor frame --}}
                <div class="overflow-hidden rounded-2xl bg-white shadow-lift ring-1 ring-ink-200">
                    <div class="flex items-center gap-1.5 border-b border-ink-100 bg-ink-50/70 px-4 py-3">
                        <span class="size-2.5 rounded-full bg-ink-300"></span>
                        <span class="size-2.5 rounded-full bg-ink-300"></span>
                        <span class="size-2.5 rounded-full bg-ink-300"></span>
                        <span class="ms-3 h-2 w-32 rounded-full bg-ink-200"></span>
                    </div>
                    <div class="grid grid-cols-12 gap-0">
                        <div class="col-span-4 space-y-2 border-e border-ink-100 p-4">
                            @foreach (['w-4/5', 'w-3/5', 'w-2/3', 'w-1/2', 'w-3/4'] as $i => $width)
                                <div @class(['flex items-center gap-2 rounded-lg px-2 py-1.5', 'bg-brand-50' => $i === 1])>
                                    <span @class(['size-2.5 rounded', 'bg-brand-500' => $i === 1, 'bg-ink-200' => $i !== 1])></span>
                                    <span @class(['h-1.5 rounded-full', $width, 'bg-brand-300' => $i === 1, 'bg-ink-200' => $i !== 1])></span>
                                </div>
                            @endforeach
                        </div>
                        <div class="col-span-8 bg-ink-50/60 p-5">
                            <x-landing.cv-mock variant="modern-professional" class="mx-auto max-w-[220px] shadow-lift" />
                        </div>
                    </div>
                </div>

                {{-- Floating chips --}}
                <div class="absolute -start-6 top-1/3 hidden rounded-xl bg-white px-4 py-3 shadow-lift ring-1 ring-ink-200 sm:block">
                    <p class="text-xs font-medium text-ink-500">{{ __('landing.hero.chip_ats') }}</p>
                    <p class="mt-1 flex items-center gap-1.5 text-sm font-semibold text-emerald-600">
                        <x-heroicon-s-shield-check class="size-4" /> {{ __('enums.ats_status.ats_safe.label') }}
                    </p>
                </div>
                <div class="absolute -end-4 bottom-10 hidden items-center gap-2 rounded-xl bg-ink-900 px-4 py-3 text-white shadow-lift sm:flex">
                    <x-heroicon-o-arrow-down-tray class="size-4" />
                    <span class="text-sm font-medium">PDF · DOCX</span>
                </div>
            </div>
        </div>
    </div>
</section>
