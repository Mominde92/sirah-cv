@inject('presenter', 'App\Support\PlanPresenter')
@use('App\Enums\BillingInterval')

<x-admin.shell :title="__('admin.nav.plans')">
    <div class="mb-4 flex justify-end">
        <x-ui.button :href="route('admin.plans.create')" icon="plus">{{ __('admin.plans.new') }}</x-ui.button>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        @foreach ($plans as $plan)
            <x-ui.card>
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold text-ink-900">{{ $plan->name }}</h2>
                        <p class="text-xs text-ink-500">{{ $plan->slug }}</p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-1">
                        @if ($plan->is_default) <x-ui.badge color="brand">{{ __('admin.plans.default') }}</x-ui.badge> @endif
                        @unless ($plan->is_active) <x-ui.badge color="amber">{{ __('admin.templates.inactive') }}</x-ui.badge> @endunless
                        @unless ($plan->is_public) <x-ui.badge>{{ __('admin.plans.hidden') }}</x-ui.badge> @endunless
                    </div>
                </div>
                <p class="mt-3 text-sm text-ink-700" dir="ltr">{{ $presenter->price($plan, BillingInterval::Monthly) }} {{ __('billing.per_month') }} · {{ $presenter->price($plan, BillingInterval::Yearly) }} {{ __('billing.per_year') }}</p>
                <p class="mt-1 text-xs text-ink-500">{{ trans_choice('admin.plans.subscribers', $plan->active_subscriptions_count, ['count' => $plan->active_subscriptions_count]) }}</p>
                <ul class="mt-4 space-y-1.5 text-sm">
                    @foreach ($presenter->featureLines($plan) as $line)
                        <li @class(['flex gap-2', 'text-ink-400' => ! $line['included']])>{{ $line['included'] ? '✓' : '–' }} {{ $line['text'] }}</li>
                    @endforeach
                </ul>
                <x-ui.button :href="route('admin.plans.edit', $plan)" variant="secondary" size="sm" class="mt-5 w-full">{{ __('admin.edit') }}</x-ui.button>
            </x-ui.card>
        @endforeach
    </div>
</x-admin.shell>
