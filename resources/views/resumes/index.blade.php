<x-layouts.app :title="__('resumes.title')">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-ink-500">
                @if ($allowance->isUnlimited())
                    {{ __('resumes.count_unlimited', ['count' => $resumes->total()]) }}
                @else
                    {{ __('resumes.count_limited', ['count' => $allowance->used, 'limit' => $allowance->limit]) }}
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.button :href="route('imports.create')" variant="secondary" icon="arrow-up-tray">{{ __('resumes.import') }}</x-ui.button>
            <x-ui.button :href="route('resumes.create')" icon="plus">{{ __('resumes.new') }}</x-ui.button>
        </div>
    </div>

    @if ($resumes->isEmpty())
        <div class="mt-8 flex flex-col items-center rounded-3xl border-2 border-dashed border-ink-200 bg-white px-6 py-16 text-center">
            <span class="inline-flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                <x-heroicon-o-document-plus class="size-7" aria-hidden="true" />
            </span>
            <h2 class="mt-5 text-lg font-semibold text-ink-900">{{ __('resumes.empty.title') }}</h2>
            <p class="mt-2 max-w-md text-sm leading-6 text-ink-500">{{ __('resumes.empty.body') }}</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <x-ui.button :href="route('resumes.create')" icon="plus">{{ __('resumes.new') }}</x-ui.button>
                <x-ui.button :href="route('imports.create')" variant="secondary" icon="arrow-up-tray">{{ __('resumes.import') }}</x-ui.button>
            </div>
        </div>
    @else
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <a href="{{ route('resumes.create') }}" class="focus-ring group flex min-h-72 flex-col items-center justify-center rounded-2xl border-2 border-dashed border-ink-200 bg-white/60 p-6 text-center transition hover:border-brand-300 hover:bg-brand-50/40">
                <span class="inline-flex size-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 transition group-hover:bg-brand-600 group-hover:text-white">
                    <x-heroicon-o-plus class="size-6" aria-hidden="true" />
                </span>
                <span class="mt-4 text-sm font-semibold text-ink-900">{{ __('resumes.new') }}</span>
                <span class="mt-1 text-xs text-ink-500">{{ __('resumes.new_hint') }}</span>
            </a>

            @foreach ($resumes as $resume)
                <x-resume.card :resume="$resume" />
            @endforeach
        </div>

        <div class="mt-8">{{ $resumes->links() }}</div>
    @endif
</x-layouts.app>
