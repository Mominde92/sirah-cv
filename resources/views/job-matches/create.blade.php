<x-layouts.app :title="__('job_match.title')">
    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <h2 class="text-lg font-semibold text-ink-900">{{ __('job_match.heading') }}</h2>
            <p class="mt-1 text-sm leading-6 text-ink-500">{{ __('job_match.subtitle', ['resume' => $resume->title]) }}</p>

            <form method="POST" action="{{ route('resumes.optimize.store', $resume) }}" class="mt-6 space-y-5">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input name="job_title" :label="__('job_match.fields.job_title')" maxlength="200" />
                    <x-ui.input name="company" :label="__('job_match.fields.company')" maxlength="200" />
                </div>
                <div class="space-y-1.5">
                    <label for="job_description" class="block text-sm font-medium text-ink-700">{{ __('job_match.fields.job_description') }}</label>
                    <textarea id="job_description" name="job_description" rows="14" minlength="80" maxlength="20000" required dir="auto"
                              placeholder="{{ __('job_match.fields.placeholder') }}"
                              class="block w-full rounded-xl border-0 px-3.5 py-3 text-sm leading-6 ring-1 ring-inset ring-ink-200 focus:ring-2 focus:ring-brand-500">{{ old('job_description') }}</textarea>
                    @error('job_description') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center justify-between gap-4">
                    <p class="text-xs leading-5 text-ink-500">{{ __('job_match.honesty') }}</p>
                    <x-ui.button type="submit" icon="magnifying-glass">{{ __('job_match.submit') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card>
                <h3 class="text-sm font-semibold text-ink-900">{{ __('job_match.back_to_resume') }}</h3>
                <x-ui.button :href="route('resumes.edit', $resume)" variant="secondary" icon="pencil-square" class="mt-3 w-full">{{ $resume->title }}</x-ui.button>
            </x-ui.card>

            @if ($history->isNotEmpty())
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink-900">{{ __('job_match.history') }}</h3>
                    <ul class="mt-3 divide-y divide-ink-100">
                        @foreach ($history as $item)
                            <li class="py-2.5">
                                <a href="{{ route('job-matches.show', $item) }}" class="flex items-center justify-between gap-3 text-sm hover:text-brand-700">
                                    <span class="min-w-0 truncate text-ink-800">{{ $item->job_title ?: __('job_match.untitled') }}@if ($item->company) · {{ $item->company }}@endif</span>
                                    @if (isset($item->analysis['coverage']))<x-ui.badge color="brand">{{ $item->analysis['coverage'] }}%</x-ui.badge>@endif
                                </a>
                                <p class="text-xs text-ink-500">{{ $item->created_at->diffForHumans() }}</p>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif
        </div>
    </div>
</x-layouts.app>
