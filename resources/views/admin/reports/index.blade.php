@php
    $maxUsers = max(1, max(array_column($rows, 'users')));
@endphp

<x-admin.shell :title="__('admin.nav.reports')">
    <x-ui.card class="mb-6">
        <h2 class="text-sm font-semibold text-ink-900">{{ __('admin.reports.signups') }}</h2>
        <div class="mt-6 flex h-40 items-end gap-2" role="img" aria-label="{{ __('admin.reports.signups') }}">
            @foreach ($rows as $month => $row)
                <div class="flex flex-1 flex-col items-center gap-2">
                    <span class="text-[11px] tabular-nums text-ink-500">{{ $row['users'] }}</span>
                    <div class="w-full rounded-t-md bg-brand-500/80" style="height: {{ max(2, round($row['users'] / $maxUsers * 110)) }}px" title="{{ $month }}: {{ $row['users'] }}"></div>
                    <span class="text-[10px] text-ink-400" dir="ltr">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('M') }}</span>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    <x-admin.table>
        <x-slot:head>
            <th class="px-4 py-3 text-start">{{ __('admin.reports.month') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.overview.users') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.overview.resumes') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.nav.downloads') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.reports.revenue') }}</th>
        </x-slot:head>
        @foreach (array_reverse($rows, true) as $month => $row)
            <tr>
                <td class="px-4 py-3" dir="ltr">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}</td>
                <td class="px-4 py-3 tabular-nums">{{ number_format($row['users']) }}</td>
                <td class="px-4 py-3 tabular-nums">{{ number_format($row['resumes']) }}</td>
                <td class="px-4 py-3 tabular-nums">{{ number_format($row['exports']) }}</td>
                <td class="px-4 py-3 tabular-nums" dir="ltr">${{ number_format($row['revenue'] / 100, 2) }}</td>
            </tr>
        @endforeach
    </x-admin.table>
</x-admin.shell>
