<x-admin.shell :title="__('admin.nav.ai_usage')">
    <x-ui.alert :type="$enabled ? 'success' : 'info'" class="mb-6">
        @if ($enabled)
            {{ __('admin.ai.enabled', ['provider' => $provider, 'model' => $model]) }}
        @else
            {{ __('admin.ai.disabled') }}
        @endif
    </x-ui.alert>

    <x-admin.table class="mb-6">
        <x-slot:head>
            <th class="px-4 py-3 text-start">{{ __('admin.ai.feature') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.ai.requests') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.ai.tokens') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.ai.cost') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.ai.latency') }}</th>
        </x-slot:head>
        @forelse ($byFeature as $row)
            <tr>
                <td class="px-4 py-3">{{ __('admin.ai.features.'.$row->feature->value) }}</td>
                <td class="px-4 py-3 tabular-nums">{{ number_format($row->requests) }}</td>
                <td class="px-4 py-3 tabular-nums" dir="ltr">{{ number_format((int) $row->input_tokens) }} / {{ number_format((int) $row->output_tokens) }}</td>
                <td class="px-4 py-3 tabular-nums" dir="ltr">{{ $row->cost_micros !== null ? '$'.number_format($row->cost_micros / 1_000_000, 2) : '—' }}</td>
                <td class="px-4 py-3 tabular-nums">{{ number_format((float) $row->latency) }} ms</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-10 text-center text-ink-500">{{ __('admin.empty') }}</td></tr>
        @endforelse
    </x-admin.table>
    <p class="mb-3 text-sm text-ink-500">{{ __('admin.ai.failures', ['count' => $failures]) }}</p>

    <x-admin.table>
        <x-slot:head>
            <th class="px-4 py-3 text-start">{{ __('billing.page.date') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.users.user') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.ai.feature') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.ai.model') }}</th>
            <th class="px-4 py-3 text-start">{{ __('billing.page.status') }}</th>
        </x-slot:head>
        @foreach ($recent as $log)
            <tr>
                <td class="px-4 py-3 text-xs text-ink-500">{{ $log->created_at->diffForHumans() }}</td>
                <td class="px-4 py-3">{{ $log->user?->email ?? '—' }}</td>
                <td class="px-4 py-3">{{ __('admin.ai.features.'.$log->feature->value) }}</td>
                <td class="px-4 py-3 text-xs" dir="ltr">{{ $log->provider }} · {{ $log->model }}</td>
                <td class="px-4 py-3"><x-ui.badge :color="$log->status->value === 'succeeded' ? 'emerald' : 'amber'">{{ $log->status->value }}</x-ui.badge></td>
            </tr>
        @endforeach
    </x-admin.table>
</x-admin.shell>
