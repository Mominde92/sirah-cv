@php($analysis = $match->analysis ?? [])

<x-layouts.app :title="__('job_match.result.title')">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-ink-900">{{ $match->job_title ?: __('job_match.untitled') }}@if ($match->company) <span class="font-normal text-ink-500">· {{ $match->company }}</span>@endif</h2>
                <p class="text-sm text-ink-500">{{ __('job_match.result.for_resume', ['resume' => $match->resume?->title]) }}</p>
            </div>
            <div class="flex gap-2">
                @if ($match->resume)
                    <x-ui.button :href="route('resumes.optimize', $match->resume)" variant="secondary" icon="arrow-path">{{ __('job_match.result.again') }}</x-ui.button>
                    <x-ui.button :href="route('resumes.edit', $match->resume)" icon="pencil-square">{{ __('job_match.result.edit') }}</x-ui.button>
                @endif
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <x-ui.card>
                <p class="text-sm font-medium text-ink-500">{{ __('job_match.result.coverage') }}</p>
                <p class="mt-2 text-5xl font-semibold tracking-tight text-ink-900">{{ $analysis['coverage'] ?? 0 }}<span class="text-2xl text-ink-400">%</span></p>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-ink-100"><div class="h-full rounded-full bg-brand-500" style="width: {{ (int) ($analysis['coverage'] ?? 0) }}%"></div></div>
                <p class="mt-4 text-xs leading-5 text-ink-500">{{ __('job_match.result.coverage_note', ['found' => count($analysis['keywords_found'] ?? []), 'total' => count($analysis['keywords'] ?? [])]) }}</p>
            </x-ui.card>

            <x-ui.card class="lg:col-span-2">
                <h3 class="text-sm font-semibold text-ink-900">{{ __('job_match.result.suggestions') }}</h3>
                <ul class="mt-4 space-y-3">
                    @foreach ($analysis['suggestions'] ?? [] as $suggestion)
                        <li class="flex gap-3 text-sm leading-6 text-ink-700">
                            @if (($suggestion['source'] ?? '') === 'ai')
                                <x-heroicon-o-sparkles class="mt-1 size-4 shrink-0 text-violet-500" aria-hidden="true" />
                            @else
                                <x-heroicon-o-light-bulb class="mt-1 size-4 shrink-0 text-amber-500" aria-hidden="true" />
                            @endif
                            <span dir="auto">{{ $suggestion['text'] }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-5 rounded-lg bg-ink-50 p-3 text-xs leading-5 text-ink-500">{{ __('job_match.honesty') }}</p>
            </x-ui.card>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            @foreach (['keywords_found' => ['emerald', 'check-circle', 'text-emerald-500'], 'keywords_missing' => ['amber', 'exclamation-circle', 'text-amber-500'], 'relevant_skills' => ['brand', 'star', 'text-brand-500']] as $key => [$color, $icon, $iconClass])
                <x-ui.card>
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-ink-900">
                        <x-dynamic-component :component="'heroicon-o-'.$icon" :class="'size-4 '.$iconClass" aria-hidden="true" />
                        {{ __('job_match.result.'.$key) }} <span class="text-ink-400">({{ count($analysis[$key] ?? []) }})</span>
                    </h3>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @forelse ($analysis[$key] ?? [] as $keyword)
                            <x-ui.badge :color="$color" dir="auto">{{ $keyword }}</x-ui.badge>
                        @empty
                            <p class="text-sm text-ink-500">{{ __('job_match.result.none') }}</p>
                        @endforelse
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    </div>
</x-layouts.app>
