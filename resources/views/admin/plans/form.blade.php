@php($exists = $plan->exists)

<x-admin.shell :title="$exists ? $plan->name : __('admin.plans.new')">
    <form method="POST" action="{{ $exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($exists) @method('PUT') @endif

        <div class="space-y-6 lg:col-span-2">
            <x-ui.card>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input name="name_en" :label="__('admin.templates.name_en')" :value="$exists ? $plan->getTranslation('name', 'en', false) : ''" required />
                    <x-ui.input name="name_ar" :label="__('admin.templates.name_ar')" :value="$exists ? $plan->getTranslation('name', 'ar', false) : ''" dir="rtl" required />
                    <x-ui.input name="description_en" :label="__('admin.templates.description_en')" :value="$exists ? $plan->getTranslation('description', 'en', false) : ''" />
                    <x-ui.input name="description_ar" :label="__('admin.templates.description_ar')" :value="$exists ? $plan->getTranslation('description', 'ar', false) : ''" dir="rtl" />
                    <x-ui.input name="slug" :label="__('admin.templates.slug')" :value="$plan->slug" dir="ltr" required />
                    <x-ui.input name="currency" :label="__('admin.plans.currency')" :value="$plan->currency" maxlength="3" dir="ltr" required />
                    <x-ui.input name="price_monthly" type="number" step="0.01" min="0" :label="__('admin.plans.price_monthly')" :value="number_format(($plan->price_monthly ?? 0) / 100, 2, '.', '')" dir="ltr" required />
                    <x-ui.input name="price_yearly" type="number" step="0.01" min="0" :label="__('admin.plans.price_yearly')" :value="number_format(($plan->price_yearly ?? 0) / 100, 2, '.', '')" dir="ltr" required />
                    <x-ui.input name="trial_days" type="number" min="0" max="90" :label="__('admin.plans.trial_days')" :value="$plan->trial_days ?? 0" required />
                    <x-ui.input name="sort_order" type="number" :label="__('admin.templates.sort_order')" :value="$plan->sort_order" required />
                    <x-ui.input name="stripe_monthly" :label="__('admin.plans.stripe_monthly')" :value="$plan->provider_price_ids['stripe']['monthly'] ?? ''" placeholder="price_..." dir="ltr" />
                    <x-ui.input name="stripe_yearly" :label="__('admin.plans.stripe_yearly')" :value="$plan->provider_price_ids['stripe']['yearly'] ?? ''" placeholder="price_..." dir="ltr" />
                </div>
            </x-ui.card>

            <x-admin.table>
                <x-slot:head>
                    <th class="px-4 py-3 text-start">{{ __('admin.plans.feature') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.plans.enabled') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.plans.limit') }}</th>
                </x-slot:head>
                @foreach (\App\Enums\Feature::cases() as $feature)
                    @php($current = $exists ? $plan->feature($feature) : null)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="font-medium text-ink-900">{{ $feature->label() }}</span>
                            <span class="block text-xs text-ink-500">{{ $feature->isBoolean() ? __('admin.plans.kinds.boolean') : ($feature->isMetered() ? __('admin.plans.kinds.monthly') : __('admin.plans.kinds.total')) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <input type="hidden" name="features[{{ $feature->value }}][enabled]" value="0">
                            <input type="checkbox" name="features[{{ $feature->value }}][enabled]" value="1" @checked($current?->is_enabled) class="rounded border-ink-300 text-brand-600">
                        </td>
                        <td class="px-4 py-3">
                            @unless ($feature->isBoolean())
                                <input type="number" min="0" name="features[{{ $feature->value }}][limit]" value="{{ $current?->limit_value }}" placeholder="{{ __('admin.plans.unlimited') }}" dir="ltr"
                                       class="w-32 rounded-lg border-0 px-3 py-1.5 text-sm ring-1 ring-inset ring-ink-200 focus:ring-2 focus:ring-brand-500">
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
            <p class="text-xs text-ink-500">{{ __('admin.plans.limits_hint') }}</p>
        </div>

        <x-ui.card class="h-fit space-y-4">
            @foreach (['is_active' => 'active', 'is_public' => 'public', 'is_default' => 'is_default'] as $field => $label)
                <label class="flex items-center justify-between gap-3 text-sm">
                    <span>{{ __('admin.plans.'.$label) }}</span>
                    <input type="hidden" name="{{ $field }}" value="0">
                    <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $plan->{$field})) class="rounded border-ink-300 text-brand-600">
                </label>
            @endforeach
            <x-ui.button type="submit" class="w-full">{{ __('common.save') }}</x-ui.button>
        </x-ui.card>
    </form>
</x-admin.shell>
