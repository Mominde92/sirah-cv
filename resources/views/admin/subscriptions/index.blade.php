<x-admin.shell :title="__('admin.nav.subscriptions')">
    <form method="GET" class="mb-4 flex gap-3">
        <select name="status" class="rounded-xl border-0 py-2.5 ps-3 pe-8 text-sm ring-1 ring-inset ring-ink-200" onchange="this.form.submit()">
            <option value="">{{ __('admin.users.filters.all') }}</option>
            @foreach (\App\Enums\SubscriptionStatus::cases() as $option)
                <option value="{{ $option->value }}" @selected($status === $option->value)>{{ __('admin.subscriptions.statuses.'.$option->value) }}</option>
            @endforeach
        </select>
    </form>

    <x-admin.table>
        <x-slot:head>
            <th class="px-4 py-3 text-start">{{ __('admin.users.user') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.users.plan') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.users.status') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.subscriptions.provider') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.subscriptions.period') }}</th>
        </x-slot:head>
        @forelse ($subscriptions as $subscription)
            <tr>
                <td class="px-4 py-3">@if ($subscription->user)<a href="{{ route('admin.users.show', $subscription->user) }}" class="hover:text-brand-700">{{ $subscription->user->email }}</a>@endif</td>
                <td class="px-4 py-3">{{ $subscription->plan?->name }} @if ($subscription->billing_interval)<span class="text-xs text-ink-400">· {{ __('billing.interval.'.$subscription->billing_interval->value) }}</span>@endif</td>
                <td class="px-4 py-3"><x-ui.badge :color="$subscription->isValid() ? 'emerald' : 'ink'">{{ __('admin.subscriptions.statuses.'.$subscription->status->value) }}</x-ui.badge></td>
                <td class="px-4 py-3">{{ $subscription->provider }}</td>
                <td class="px-4 py-3 text-xs text-ink-500">{{ $subscription->starts_at->translatedFormat('j M Y') }} → {{ ($subscription->ends_at ?? $subscription->current_period_ends_at)?->translatedFormat('j M Y') ?? '∞' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-10 text-center text-ink-500">{{ __('admin.empty') }}</td></tr>
        @endforelse
    </x-admin.table>
    <div class="mt-4">{{ $subscriptions->links() }}</div>
</x-admin.shell>
