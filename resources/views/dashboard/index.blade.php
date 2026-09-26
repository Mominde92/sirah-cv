<x-layouts.app :title="__('common.nav.dashboard')">
    <div class="space-y-8">
        {{-- Welcome --}}
        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 px-6 py-8 text-white shadow-lift sm:px-10">
            <div class="absolute -end-16 -top-16 size-64 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-brand-200">{{ __('dashboard.greeting.'.$greeting) }}</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">{{ $user->name }}</h2>
                    <p class="mt-2 max-w-lg text-sm leading-6 text-brand-100">{{ __('dashboard.welcome_body') }}</p>
                </div>
                <x-ui.badge color="brand" class="self-start bg-white/15 px-3 py-1 text-white ring-white/25 sm:self-center">
                    <x-heroicon-s-star class="size-3.5" aria-hidden="true" />
                    {{ __('dashboard.plan_badge', ['plan' => $plan->name]) }}
                </x-ui.badge>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- My resumes --}}
            <x-ui.card class="lg:col-span-2">
                <x-slot:header>
                    <div>
                        <h2 class="text-base font-semibold text-ink-900">{{ __('dashboard.resumes.title') }}</h2>
                        <p class="mt-1 text-sm text-ink-500">{{ __('dashboard.resumes.subtitle') }}</p>
                    </div>
                    @if ($recentResumes->isNotEmpty())
                        <a href="{{ route('resumes.index') }}" class="shrink-0 text-sm font-semibold text-brand-600 hover:text-brand-700">{{ __('dashboard.resumes.view_all') }}</a>
                    @endif
                </x-slot:header>

                @if ($recentResumes->isEmpty())
                    <div class="flex flex-col items-center rounded-2xl border-2 border-dashed border-ink-200 px-6 py-12 text-center">
                        <span class="inline-flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                            <x-heroicon-o-document-text class="size-7" aria-hidden="true" />
                        </span>
                        <h3 class="mt-5 text-base font-semibold text-ink-900">{{ __('dashboard.resumes.empty_title') }}</h3>
                        <p class="mt-2 max-w-sm text-sm leading-6 text-ink-500">{{ __('dashboard.resumes.empty_body') }}</p>
                        <div class="mt-6 flex flex-wrap justify-center gap-3">
                            <x-ui.button :href="route('resumes.create')" icon="plus">{{ __('resumes.new') }}</x-ui.button>
                            <x-ui.button :href="route('imports.create')" variant="secondary" icon="arrow-up-tray">{{ __('resumes.import') }}</x-ui.button>
                        </div>
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach ($recentResumes as $resume)
                            <a href="{{ route('resumes.edit', $resume) }}" class="focus-ring group block">
                                <div class="rounded-xl bg-ink-50 p-3 ring-1 ring-ink-200/70 transition group-hover:ring-brand-300">
                                    <x-resume.thumbnail :src="route('resumes.preview', ['resume' => $resume, 'thumb' => 1, 'v' => $resume->updated_at?->timestamp])" :label="$resume->title" class="rounded shadow-soft" />
                                </div>
                                <p class="mt-2 truncate text-sm font-semibold text-ink-900">{{ $resume->title }}</p>
                                <p class="truncate text-xs text-ink-500">{{ __('resumes.edited', ['time' => $resume->updated_at?->diffForHumans()]) }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            {{-- Plan & usage --}}
            <x-ui.card>
                <x-slot:header>
                    <div>
                        <h2 class="text-base font-semibold text-ink-900">{{ __('dashboard.usage.title') }}</h2>
                        <p class="mt-1 text-xs text-ink-500">{{ __('dashboard.usage.resets', ['date' => $periodEndsAt->translatedFormat('j F')]) }}</p>
                    </div>
                </x-slot:header>

                <ul class="space-y-5">
                    @foreach ($usage as $allowance)
                        <li>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-medium text-ink-700">{{ $allowance->feature->label() }}</span>
                                <span class="tabular-nums text-ink-500">
                                    @if (! $allowance->enabled)
                                        <span class="inline-flex items-center gap-1 text-ink-400"><x-heroicon-o-lock-closed class="size-3.5" aria-hidden="true" /> {{ __('dashboard.usage.locked') }}</span>
                                    @elseif ($allowance->isUnlimited())
                                        {{ __('dashboard.usage.unlimited') }}
                                    @else
                                        {{ __('dashboard.usage.count', ['used' => $allowance->used, 'limit' => $allowance->limit]) }}
                                    @endif
                                </span>
                            </div>
                            @if ($allowance->percentUsed() !== null)
                                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-ink-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $allowance->percentUsed() }}" aria-label="{{ $allowance->feature->label() }}">
                                    <div @class([
                                        'h-full rounded-full transition-all',
                                        'bg-brand-500' => $allowance->percentUsed() < 80,
                                        'bg-amber-500' => $allowance->percentUsed() >= 80 && $allowance->percentUsed() < 100,
                                        'bg-red-500' => $allowance->percentUsed() >= 100,
                                    ]) style="width: {{ $allowance->percentUsed() }}%"></div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <div class="mt-6 rounded-xl bg-ink-50 p-4">
                    <p class="text-sm font-semibold text-ink-900">{{ $plan->name }}</p>
                    <p class="mt-1 text-xs leading-5 text-ink-500">
                        @if ($subscription?->current_period_ends_at)
                            {{ __('dashboard.subscription.renews', ['date' => $subscription->current_period_ends_at->translatedFormat('j F Y')]) }}
                        @elseif ($plan->isFree())
                            {{ __('dashboard.subscription.free') }}
                        @else
                            {{ __('dashboard.subscription.active') }}
                        @endif
                    </p>
                    </p>
                    <a href="{{ route('billing.index') }}" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:text-brand-700">
                        {{ __('billing.see_plans') }}
                        <x-heroicon-o-arrow-right class="size-3.5 flip-rtl" aria-hidden="true" />
                    </a>
                </div>
            </x-ui.card>
        </div>

        {{-- Quick actions --}}
        @php
            $quickActions = [
                ['route' => route('resumes.create'), 'icon' => 'document-plus', 'key' => 'create'],
                ['route' => route('imports.create'), 'icon' => 'arrow-up-tray', 'key' => 'import'],
                ['route' => route('designs.create'), 'icon' => 'photo', 'key' => 'design'],
                ['route' => route('templates.index'), 'icon' => 'swatch', 'key' => 'templates'],
            ];
        @endphp
        <section>
            <h2 class="text-base font-semibold text-ink-900">{{ __('dashboard.quick.title') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($quickActions as $action)
                    <a href="{{ $action['route'] }}" class="focus-ring group flex items-start gap-4 rounded-2xl bg-white p-5 shadow-soft ring-1 ring-ink-200/70 transition hover:ring-brand-300">
                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-ink-100 text-ink-600 group-hover:bg-brand-50 group-hover:text-brand-600">
                            <x-dynamic-component :component="'heroicon-o-'.$action['icon']" class="size-5" aria-hidden="true" />
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-ink-900">{{ __('dashboard.quick.'.$action['key'].'.title') }}</span>
                            <span class="mt-1 block text-sm text-ink-500">{{ __('dashboard.quick.'.$action['key'].'.body') }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>