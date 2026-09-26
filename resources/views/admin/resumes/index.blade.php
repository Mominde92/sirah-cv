<x-admin.shell :title="__('admin.nav.resumes')">
    <form method="GET" class="mb-4 flex gap-3">
        <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('admin.resumes.search') }}" class="w-full max-w-sm rounded-xl border-0 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-ink-200 focus:ring-2 focus:ring-brand-500">
        <x-ui.button type="submit" variant="secondary">{{ __('admin.filter') }}</x-ui.button>
    </form>
    <p class="mb-4 text-xs text-ink-500">{{ __('admin.resumes.privacy') }}</p>

    <x-admin.table>
        <x-slot:head>
            <th class="px-4 py-3 text-start">{{ __('admin.resumes.title') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.users.user') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.resumes.template') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.resumes.source') }}</th>
            <th class="px-4 py-3 text-start">{{ __('admin.resumes.updated') }}</th>
        </x-slot:head>
        @forelse ($resumes as $resume)
            <tr>
                <td class="px-4 py-3">{{ $resume->title }} <span class="text-xs text-ink-400">{{ strtoupper($resume->language) }}</span></td>
                <td class="px-4 py-3">@if ($resume->user)<a href="{{ route('admin.users.show', $resume->user) }}" class="hover:text-brand-700">{{ $resume->user->email }}</a>@endif</td>
                <td class="px-4 py-3">{{ $resume->template?->name }}</td>
                <td class="px-4 py-3"><x-ui.badge>{{ __('admin.resumes.sources.'.$resume->source->value) }}</x-ui.badge></td>
                <td class="px-4 py-3 text-xs text-ink-500">{{ $resume->updated_at->diffForHumans() }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-10 text-center text-ink-500">{{ __('admin.empty') }}</td></tr>
        @endforelse
    </x-admin.table>
    <div class="mt-4">{{ $resumes->links() }}</div>
</x-admin.shell>
