<x-admin.shell :title="__('admin.nav.downloads')">
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach (['pdf', 'docx'] as $format)
            @foreach (['completed', 'failed'] as $status)
                <x-admin.stat :label="strtoupper($format).' · '.__('imports.status.'.$status)" :value="number_format((int) ($summary->first(fn ($row) => $row->format->value === $format && $row->status->value === $status)?->total ?? 0))" :hint="__('admin.last_30_days')" :icon="$status === 'failed' ? 'exclamation-triangle' : 'arrow-down-tray'" />
            @endforeach
        @endforeach
    </div>

    <x-admin.table>
        <x-slot:head>
            <th class="px-4 py-3 text-start">{{ __('billing.page.date') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.users.user') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.resumes.title') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.downloads.format') }}</th>
            <th class="px-4 py-3 text-start">{{ __('billing.page.status') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.downloads.size') }}</th>
        </x-slot:head>
        @forelse ($exports as $export)
            <tr>
                <td class="px-4 py-3 text-xs text-ink-500">{{ $export->created_at->diffForHumans() }}</td>
                <td class="px-4 py-3">{{ $export->user?->email }}</td>
                <td class="px-4 py-3">{{ $export->resume?->title ?? '—' }}</td>
                <td class="px-4 py-3 uppercase">{{ $export->format->value }}</td>
                <td class="px-4 py-3"><x-ui.badge :color="match ($export->status->value) { 'completed' => 'emerald', 'failed' => 'amber', default => 'brand' }">{{ __('imports.status.'.$export->status->value) }}</x-ui.badge></td>
                <td class="px-4 py-3 tabular-nums" dir="ltr">{{ $export->file_size ? \Illuminate\Support\Number::fileSize($export->file_size) : '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-10 text-center text-ink-500">{{ __('admin.empty') }}</td></tr>
        @endforelse
    </x-admin.table>
    <div class="mt-4">{{ $exports->links() }}</div>
</x-admin.shell>
