@php($money = fn (int $minor, string $currency) => \Illuminate\Support\Number::currency($minor / 100, in: $currency, locale: 'en'))

<x-admin.shell :title="__('admin.nav.payments')">
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        @forelse ($totals as $total)
            <x-admin.stat :label="__('admin.payments.total', ['currency' => $total->currency])" :value="$money((int) $total->total, $total->currency)" :hint="trans_choice('admin.payments.count', $total->count, ['count' => $total->count])" icon="banknotes" />
        @empty
            <x-admin.stat :label="__('admin.payments.total', ['currency' => 'USD'])" :value="$money(0, 'USD')" icon="banknotes" />
        @endforelse
    </div>

    <x-admin.table>
        <x-slot:head>
            <th class="px-4 py-3 text-start">{{ __('billing.page.date') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.users.user') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.users.plan') }}</th>
            <th class="px-4 py-3 text-start">{{ __('billing.page.amount') }}</th>
            <th class="px-4 py-3 text-start">{{ __('billing.page.status') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.subscriptions.provider') }}</th>
        </x-slot:head>
        @forelse ($payments as $payment)
            <tr>
                <td class="px-4 py-3 text-xs text-ink-500">{{ ($payment->paid_at ?? $payment->created_at)->translatedFormat('j M Y H:i') }}</td>
                <td class="px-4 py-3">{{ $payment->user?->email ?? '—' }}</td>
                <td class="px-4 py-3">{{ $payment->plan?->name ?? '—' }}</td>
                <td class="px-4 py-3 tabular-nums" dir="ltr">{{ $money($payment->amount, $payment->currency) }}</td>
                <td class="px-4 py-3"><x-ui.badge :color="$payment->status === \App\Enums\PaymentStatus::Succeeded ? 'emerald' : 'amber'">{{ __('billing.page.payment_status.'.$payment->status->value) }}</x-ui.badge></td>
                <td class="px-4 py-3 text-xs">{{ $payment->provider }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-10 text-center text-ink-500">{{ __('admin.empty') }}</td></tr>
        @endforelse
    </x-admin.table>
    <div class="mt-4">{{ $payments->links() }}</div>
</x-admin.shell>
