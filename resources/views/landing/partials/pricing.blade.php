@inject('presenter', 'App\Support\PlanPresenter')
@use('App\Enums\BillingInterval')

@php
    $highlighted = config('cv.pricing.highlighted_plan');
    $maxSaving = $plans->map(fn ($plan) => $presenter->yearlySavingPercent($plan))->max() ?? 0;
    // Full class names so Tailwind's scanner generates them.
    $gridColumns = [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4'][max(1, min(4, $plans->count()))];
@endphp

<section id="pricing" x-data="{ yearly: false }" class="scroll-mt-20 bg-ink-50/70 py-24">
    <div class="container-page">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold text-brand-600">{{ __('landing.pricing.eyebrow') }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-balance text-ink-900 sm:text-4xl">{{ __('landing.pricing.title') }}</h2>
            <p class="mt-4 text-lg leading-8 text-ink-600">{{ __('landing.pricing.subtitle') }}</p>
        </div>

        <div class="mt-10 flex justify-center">
            <div class="inline-flex items-center gap-1 rounded-full bg-white p-1 shadow-soft ring-1 ring-ink-200" role="group" aria-label="{{ __('landing.pricing.billing_period') }}">
                <button type="button" @click="yearly = false" :aria-pressed="(! yearly).toString()"
                        :class="! yearly ? 'bg-ink-900 text-white' : 'text-ink-600 hover:text-ink-900'"
                        class="focus-ring rounded-full px-4 py-2 text-sm font-medium transition">
                    {{ __('billing.interval.monthly') }}
                </button>
                <button type="button" @click="yearly = true" :aria-pressed="yearly.toString()"
                        :class="yearly ? 'bg-ink-900 text-white' : 'text-ink-600 hover:text-ink-900'"
                        class="focus-ring inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition">
                    {{ __('billing.interval.yearly') }}
                    @if ($maxSaving > 0)
                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">{{ __('landing.pricing.save_up_to', ['percent' => $maxSaving]) }}</span>
                    @endif
                </button>
            </div>
        </div>

        <div class="mx-auto mt-12 grid max-w-6xl gap-6 {{ $gridColumns }}">
            @forelse ($plans as $plan)
                @php($isHighlighted = $plan->slug === $highlighted)
                <div @class([
                    'relative flex flex-col rounded-3xl p-8',
                    'bg-ink-900 text-white shadow-lift ring-1 ring-ink-900' => $isHighlighted,
                    'bg-white shadow-soft ring-1 ring-ink-200' => ! $isHighlighted,
                ])>
                    @if ($isHighlighted)
                        <span class="absolute -top-3 start-8 rounded-full bg-gradient-to-r from-brand-500 to-violet-500 px-3 py-1 text-xs font-semibold text-white shadow">
                            {{ __('landing.pricing.popular') }}
                        </span>
                    @endif

                    <h3 @class(['text-lg font-semibold', 'text-ink-900' => ! $isHighlighted])>{{ $plan->name }}</h3>
                    <p @class(['mt-2 min-h-12 text-sm leading-6', 'text-ink-300' => $isHighlighted, 'text-ink-500' => ! $isHighlighted])>{{ $plan->description }}</p>

                    <div class="mt-6 flex items-baseline gap-1.5">
                        @if ($plan->isFree())
                            <span class="text-4xl font-semibold tracking-tight">{{ __('billing.free') }}</span>
                        @else
                            <span class="text-4xl font-semibold tracking-tight" x-text="yearly ? @js($presenter->price($plan, BillingInterval::Yearly)) : @js($presenter->price($plan, BillingInterval::Monthly))">
                                {{ $presenter->price($plan, BillingInterval::Monthly) }}
                            </span>
                            <span @class(['text-sm', 'text-ink-300' => $isHighlighted, 'text-ink-500' => ! $isHighlighted])
                                  x-text="yearly ? @js(__('billing.per_year')) : @js(__('billing.per_month'))">{{ __('billing.per_month') }}</span>
                        @endif
                    </div>

                    <x-ui.button
                        :href="auth()->check() ? ($plan->isFree() ? route('dashboard') : route('billing.index')) : route('register')"
                        :variant="$isHighlighted ? 'light' : ($plan->isFree() ? 'secondary' : 'primary')"
                        class="mt-8 w-full">
                        {{ $plan->isFree() ? __('landing.cta.start_free') : __('landing.pricing.choose', ['plan' => $plan->name]) }}
                    </x-ui.button>

                    <ul class="mt-8 space-y-3 text-sm">
                        @foreach ($presenter->featureLines($plan) as $line)
                            <li @class(['flex gap-3', 'opacity-50' => ! $line['included']])>
                                @if ($line['included'])
                                    <x-heroicon-s-check :class="$isHighlighted ? 'mt-0.5 size-4 shrink-0 text-brand-300' : 'mt-0.5 size-4 shrink-0 text-brand-600'" aria-hidden="true" />
                                    <span>{{ $line['text'] }}</span>
                                @else
                                    <x-heroicon-o-minus class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                    <span><span class="sr-only">{{ __('landing.pricing.not_included') }}: </span>{{ $line['text'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="text-center text-ink-500 lg:col-span-full">{{ __('landing.pricing.empty') }}</p>
            @endforelse
        </div>
    </div>
</section>
