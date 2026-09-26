<section id="templates" class="scroll-mt-20 bg-ink-50/70 py-24">
    <div class="container-page">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold text-brand-600">{{ __('landing.templates.eyebrow') }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-balance text-ink-900 sm:text-4xl">{{ __('landing.templates.title') }}</h2>
            <p class="mt-4 text-lg leading-8 text-ink-600">{{ __('landing.templates.subtitle') }}</p>
        </div>

        <ul class="mt-14 grid grid-cols-2 gap-5 sm:gap-6 md:grid-cols-4">
            @foreach ($templates as $template)
                <li class="group">
                    <a href="{{ route('templates.index') }}" class="block rounded-2xl bg-white p-3 ring-1 ring-ink-200/70 transition duration-200 group-hover:-translate-y-1 group-hover:shadow-lift">
                        {{-- Real renders of each template with sample content, lazy-loaded. --}}
                        <x-resume.thumbnail :src="route('templates.preview', ['template' => $template, 'thumb' => 1]).'&lang='.app()->getLocale()" :label="$template->name" class="rounded-lg ring-1 ring-ink-100" />
                    </a>
                    <div class="mt-3 flex items-center justify-between gap-2 px-1">
                        <p class="truncate text-sm font-semibold text-ink-900">{{ $template->name }}</p>
                        <x-template.badge :status="$template->ats_status" class="shrink-0" />
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-10 text-center">
            <x-ui.button :href="route('templates.index')" variant="secondary" icon-trailing="arrow-right">{{ __('landing.hero.browse_templates') }}</x-ui.button>
        </div>
    </div>
</section>
