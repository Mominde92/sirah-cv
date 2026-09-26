@php($money = fn (int $minor) => \Illuminate\Support\Number::currency($minor / 100, in: 'USD', locale: 'en'))

<x-admin.shell :title="__('admin.nav.overview')">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat :label="__('admin.overview.users')" :value="number_format($stats['users'])" :hint="__('admin.overview.new_30d', ['count' => $stats['users_30d']])" icon="users" />
        <x-admin.stat :label="__('admin.overview.resumes')" :value="number_format($stats['resumes'])" :hint="__('admin.overview.new_30d', ['count' => $stats['resumes_30d']])" icon="document-text" />
        <x-admin.stat :label="__('admin.overview.subscriptions')" :value="number_format($stats['subscriptions'])" icon="arrow-path-rounded-square" />
        <x-admin.stat :label="__('admin.overview.revenue_30d')" :value="$money($stats['revenue_30d'])" icon="banknotes" />
        <x-admin.stat :label="__('admin.overview.pdf_30d')" :value="number_format($stats['pdf_30d'])" icon="document-arrow-down" />
        <x-admin.stat :label="__('admin.overview.docx_30d')" :value="number_format($stats['docx_30d'])" :hint="__('admin.overview.failed', ['count' => $stats['exports_failed_30d']])" icon="document-text" />
        <x-admin.stat :label="__('admin.overview.imports_30d')" :value="number_format($stats['imports_30d'])" icon="arrow-up-tray" />
        <x-admin.stat :label="__('admin.overview.ai_30d')" :value="number_format($stats['ai_30d'])" :hint="__('admin.overview.tokens', ['count' => number_format($stats['ai_tokens_30d'])])" icon="sparkles" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-ui.card>
            <x-slot:header><h2 class="text-sm font-semibold text-ink-900">{{ __('admin.overview.latest_users') }}</h2><a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-brand-600">{{ __('dashboard.resumes.view_all') }}</a></x-slot:header>
            <ul class="divide-y divide-ink-100">
                @foreach ($latestUsers as $user)
                    <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <a href="{{ route('admin.users.show', $user) }}" class="min-w-0"><span class="block truncate font-medium text-ink-900">{{ $user->name }}</span><span class="block truncate text-xs text-ink-500">{{ $user->email }}</span></a>
                        <span class="shrink-0 text-xs text-ink-500">{{ $user->created_at->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header><h2 class="text-sm font-semibold text-ink-900">{{ __('admin.overview.top_templates') }}</h2></x-slot:header>
            <ul class="space-y-3">
                @php($max = max(1, (int) $topTemplates->max('total')))
                @foreach ($topTemplates as $row)
                    <li>
                        <div class="flex justify-between text-sm"><span class="text-ink-700">{{ $row->template?->name }}</span><span class="tabular-nums text-ink-500">{{ $row->total }}</span></div>
                        <div class="mt-1.5 h-1.5 rounded-full bg-ink-100"><div class="h-full rounded-full bg-brand-500" style="width: {{ round($row->total / $max * 100) }}%"></div></div>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    </div>
</x-admin.shell>
