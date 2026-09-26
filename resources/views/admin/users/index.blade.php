<x-admin.shell :title="__('admin.nav.users')">
    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('admin.users.search') }}" class="w-full max-w-sm rounded-xl border-0 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-ink-200 focus:ring-2 focus:ring-brand-500">
        <select name="filter" class="rounded-xl border-0 py-2.5 ps-3 pe-8 text-sm ring-1 ring-inset ring-ink-200">
            <option value="">{{ __('admin.users.filters.all') }}</option>
            @foreach (['admins', 'suspended', 'unverified'] as $option)
                <option value="{{ $option }}" @selected($filter === $option)>{{ __('admin.users.filters.'.$option) }}</option>
            @endforeach
        </select>
        <x-ui.button type="submit" variant="secondary">{{ __('admin.filter') }}</x-ui.button>
    </form>

    <x-admin.table>
        <x-slot:head>
            <th class="px-4 py-3 text-start">{{ __('admin.users.user') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.users.plan') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.nav.resumes') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.users.status') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.users.joined') }}</th>
        </x-slot:head>
        @forelse ($users as $user)
            <tr class="hover:bg-ink-50/60">
                <td class="px-4 py-3"><a href="{{ route('admin.users.show', $user) }}" class="font-medium text-ink-900 hover:text-brand-700">{{ $user->name }}</a><span class="block text-xs text-ink-500">{{ $user->email }}</span></td>
                <td class="px-4 py-3">{{ $user->subscription?->isValid() ? $user->subscription->plan?->name : __('admin.users.default_plan') }}</td>
                <td class="px-4 py-3 tabular-nums">{{ $user->resumes_count }}</td>
                <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-1">
                        @if ($user->isAdmin()) <x-ui.badge color="violet">{{ __('admin.users.admin') }}</x-ui.badge> @endif
                        @if ($user->isSuspended()) <x-ui.badge color="amber">{{ __('admin.users.suspended_badge') }}</x-ui.badge> @endif
                        @unless ($user->hasVerifiedEmail()) <x-ui.badge>{{ __('admin.users.unverified') }}</x-ui.badge> @endunless
                    </div>
                </td>
                <td class="px-4 py-3 text-xs text-ink-500">{{ $user->created_at->translatedFormat('j M Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-10 text-center text-ink-500">{{ __('admin.empty') }}</td></tr>
        @endforelse
    </x-admin.table>
    <div class="mt-4">{{ $users->links() }}</div>
</x-admin.shell>
