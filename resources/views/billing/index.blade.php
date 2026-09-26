@inject('presenter', 'App\Support\PlanPresenter')
@use('App\Enums\BillingInterval')

<x-layouts.app :title="__('billing.page.title')">
    <div class="space-y-6" x-data="{ yearly: false }">
        @error('billing') <x-ui.alert type="error">{{ $message }}</x-ui.alert> @enderror

        <div class="grid gap-6 lg:grid-cols-3">
            <x-ui.card class="lg:col-span-2">
                <p class="text-sm text-ink-500">{{ __('billing.page.current_plan') }}</p>
                <div class="mt-1 flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl font-semibold text-ink-900">{{ $plan->name }}</h2>
                    @if ($subscription?->status === \App\Enums\SubscriptionStatus::Trialing) <x-ui.badge color="violet">{{ __('billing.page.trial') }}</x-ui.badge> @endif
                    @if ($subscription?->status === \App\Enums\SubscriptionStatus::PastDue) <x-ui.badge color="amber">{{ __('billing.page.past_due') }}</x-ui.badge> @endif
                    @if ($subscription?->canceled_at) <x-ui.badge color="amber">{{ __('billing.page.ends_on', ['date' => $subscription->ends_at?->translatedFormat('j F Y')]) }}</x-ui.badge> @endif
                </div>
                <p class="mt-2 text-sm text-ink-500">
                    @if ($subscription?->current_period_ends_at && ! $subscription->canceled_at)
                        {{ __('dashboard.subscription.renews', ['date' => $subscription->current_period_ends_at->translatedFormat('j F Y')]) }}
                    @elseif ($plan->isFree())
                        {{ __('dashboard.subscription.free') }}
                    @endif
                </p>

                @if ($subscription && $subscription->provider !== 'manual')
                    <div class="mt-5 flex gap-3">
                        @if ($subscription->canceled_at)
                            <form method="POST" action="{{ route('billing.resume') }}">@csrf <x-ui.button type="submit" size="sm">{{ __('billing.page.resume') }}</x-ui.button></form>
                        @else
                            <form method="POST" action="{{ route('billing.cancel') }}" x-data @submit="if (! confirm(@js(__('billing.page.cancel_confirm')))) $event.preventDefault()">@csrf <x-ui.button type="submit" size="sm" variant="secondary">{{ __('billing.page.cancel') }}</x-ui.button></form>
                        @endif
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card>
                <h3 class="text-sm font-semibold text-ink-900">{{ __('dashboard.usage.title') }}</h3>
                <p class="text-xs text-ink-500">{{ __('dashboard.usage.resets', ['date' => $periodEndsAt->translatedFormat('j F')]) }}</p>
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ($usage as $allowance)
                        @continue($allowance->feature->isBoolean())
                        <li class="flex items-center justify-between gap-3">
                            <span class="text-ink-600">{{ $allowance->feature->label() }}</span>
                            <span class="tabular-nums font-medium text-ink-900">
                                @if (! $allowance->enabled) <x-heroicon-o-lock-closed class="inline size-3.5 text-ink-400" />
                                @elseif ($allowance->isUnlimited()) {{ __('dashboard.usage.unlimited') }}
                                @else {{ __('dashboard.usage.count', ['used' => $allowance->used, 'limit' => $allowance->limit]) }} @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        </div>

        <div class="flex items-center justify-between gap-4">
            <h2 class="text-base font-semibold text-ink-900">{{ __('billing.page.change_plan') }}</h2>
            <div class="inline-flex rounded-full bg-white p-1 shadow-soft ring-1 ring-ink-200">
                <button type="button" @click="yearly = false" :class="! yearly ? 'bg-ink-900 text-white' : 'text-ink-600'" class="rounded-full px-4 py-1.5 text-sm font-medium">{{ __('billing.interval.monthly') }}</button>
                <button type="button" @click="yearly = true" :class="yearly ? 'bg-ink-900 text-white' : 'text-ink-600'" class="rounded-full px-4 py-1.5 text-sm font-medium">{{ __('billing.interval.yearly') }}</button>
            </div>
        </div>

        @unless ($checkoutEnabled)
            <x-ui.alert type="info">{{ __('billing.page.manual_notice') }}</x-ui.alert>
        @endunless

        <div class="grid gap-6 lg:grid-cols-3">
            @foreach ($plans as $option)
                @php($isCurrent = $option->id === $plan->id)
                <div @class(['flex flex-col rounded-2xl bg-white p-6 shadow-soft ring-1', 'ring-2 ring-brand-500' => $isCurrent, 'ring-ink-200' => ! $isCurrent])>
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-ink-900">{{ $option->name }}</h3>
                        @if ($isCurrent) <x-ui.badge color="brand">{{ __('billing.page.current') }}</x-ui.badge> @endif
                    </div>
                    <p class="mt-3 text-3xl font-semibold text-ink-900">
                        @if ($option->isFree())
                            {{ __('billing.free') }}
                        @else
                            <span x-text="yearly ? @js($presenter->price($option, BillingInterval::Yearly)) : @js($presenter->price($option, BillingInterval::Monthly))">{{ $presenter->price($option, BillingInterval::Monthly) }}</span>
                            <span class="text-sm font-normal text-ink-500" x-text="yearly ? @js(__('billing.per_year')) : @js(__('billing.per_month'))">{{ __('billing.per_month') }}</span>
                        @endif
                    </p>
                    <ul class="mt-5 flex-1 space-y-2 text-sm">
                        @foreach ($presenter->featureLines($option) as $line)
                            <li @class(['flex gap-2', 'text-ink-400' => ! $line['included'], 'text-ink-700' => $line['included']])>
                                @if ($line['included']) <x-heroicon-s-check class="mt-0.5 size-4 shrink-0 text-brand-600" /> @else <x-heroicon-o-minus class="mt-0.5 size-4 shrink-0" /> @endif
                                {{ $line['text'] }}
                            </li>
                        @endforeach
                    </ul>
                    @if (! $isCurrent && ! $option->isFree())
                        <form method="POST" action="{{ route('billing.checkout') }}" class="mt-6">
                            @csrf
                            <input type="hidden" name="plan" value="{{ $option->slug }}">
                            <input type="hidden" name="interval" :value="yearly ? 'yearly' : 'monthly'" value="monthly">
                            <x-ui.button type="submit" class="w-full">{{ __('landing.pricing.choose', ['plan' => $option->name]) }}</x-ui.button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($payments->isNotEmpty())
            <x-ui.card>
                <h2 class="text-sm font-semibold text-ink-900">{{ __('billing.page.payments') }}</h2>
                <table class="mt-4 w-full text-sm">
                    <thead class="text-start text-xs text-ink-500">
                        <tr><th class="py-2 text-start font-medium">{{ __('billing.page.date') }}</th><th class="py-2 text-start font-medium">{{ __('billing.page.amount') }}</th><th class="py-2 text-start font-medium">{{ __('billing.page.status') }}</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($payments as $payment)
                            <tr>
                                <td class="py-2.5 text-ink-700">{{ ($payment->paid_at ?? $payment->created_at)->translatedFormat('j M Y') }}</td>
                                <td class="py-2.5 tabular-nums text-ink-900" dir="ltr">{{ \Illuminate\Support\Number::currency($payment->amount / 100, in: $payment->currency, locale: 'en') }}</td>
                                <td class="py-2.5"><x-ui.badge :color="$payment->status === \App\Enums\PaymentStatus::Succeeded ? 'emerald' : 'amber'">{{ __('billing.page.payment_status.'.$payment->status->value) }}</x-ui.badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.card>
        @endif
    </div>
</x-layouts.app>
