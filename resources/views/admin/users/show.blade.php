<x-admin.shell :title="$user->name">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-ink-900">{{ $user->name }}</h2>
                        <p class="text-sm text-ink-500">{{ $user->email }}</p>
                        <div class="mt-2 flex flex-wrap gap-1">
                            <x-ui.badge color="brand">{{ $plan->name }}</x-ui.badge>
                            @if ($user->isAdmin()) <x-ui.badge color="violet">{{ __('admin.users.admin') }}</x-ui.badge> @endif
                            @if ($user->isSuspended()) <x-ui.badge color="amber">{{ __('admin.users.suspended_badge') }}</x-ui.badge> @endif
                        </div>
                    </div>
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                        <dt class="text-ink-500">{{ __('admin.users.joined') }}</dt><dd class="text-ink-900">{{ $user->created_at->translatedFormat('j M Y') }}</dd>
                        <dt class="text-ink-500">{{ __('admin.users.last_login') }}</dt><dd class="text-ink-900">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</dd>
                        <dt class="text-ink-500">{{ __('admin.nav.resumes') }}</dt><dd class="text-ink-900">{{ $user->resumes_count }}</dd>
                        <dt class="text-ink-500">{{ __('admin.nav.downloads') }}</dt><dd class="text-ink-900">{{ $user->exports_count }}</dd>
                        <dt class="text-ink-500">{{ __('admin.users.ai_calls') }}</dt><dd class="text-ink-900">{{ $user->ai_usage_logs_count }}</dd>
                    </dl>
                </div>
            </x-ui.card>

            <x-admin.table>
                <x-slot:head>
                    <th class="px-4 py-3 text-start">{{ __('admin.resumes.title') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.resumes.template') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.resumes.updated') }}</th>
                </x-slot:head>
                @forelse ($resumes as $resume)
                    <tr><td class="px-4 py-3">{{ $resume->title }} <span class="text-xs text-ink-400">{{ strtoupper($resume->language) }}</span></td><td class="px-4 py-3">{{ $resume->template?->name }}</td><td class="px-4 py-3 text-xs text-ink-500">{{ $resume->updated_at->diffForHumans() }}</td></tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-ink-500">{{ __('admin.empty') }}</td></tr>
                @endforelse
            </x-admin.table>

            <x-admin.table>
                <x-slot:head>
                    <th class="px-4 py-3 text-start">{{ __('admin.users.plan') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.users.status') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.subscriptions.provider') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.subscriptions.period') }}</th>
                </x-slot:head>
                @forelse ($subscriptions as $subscription)
                    <tr><td class="px-4 py-3">{{ $subscription->plan?->name }}</td><td class="px-4 py-3">{{ __('admin.subscriptions.statuses.'.$subscription->status->value) }}</td><td class="px-4 py-3">{{ $subscription->provider }}</td><td class="px-4 py-3 text-xs text-ink-500">{{ $subscription->starts_at->translatedFormat('j M Y') }} → {{ ($subscription->ends_at ?? $subscription->current_period_ends_at)?->translatedFormat('j M Y') ?? '∞' }}</td></tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-ink-500">{{ __('admin.users.no_subscriptions') }}</td></tr>
                @endforelse
            </x-admin.table>
        </div>

        <div class="space-y-6">
            <x-ui.card>
                <h3 class="text-sm font-semibold text-ink-900">{{ __('admin.users.grant_plan') }}</h3>
                <form method="POST" action="{{ route('admin.users.plan', $user) }}" class="mt-4 space-y-3">
                    @csrf
                    <x-ui.select name="plan_id" :label="__('admin.users.plan')" :options="$plans->pluck('name', 'id')->all()" />
                    <x-ui.input name="months" type="number" min="1" max="60" :label="__('admin.users.months')" :hint="__('admin.users.months_hint')" />
                    <x-ui.button type="submit" class="w-full">{{ __('admin.users.grant') }}</x-ui.button>
                </form>
            </x-ui.card>

            @unless ($user->is(auth()->user()))
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink-900">{{ __('admin.users.account') }}</h3>
                    <div class="mt-4 space-y-3">
                        <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex gap-2">
                            @csrf @method('PATCH')
                            <select name="role" class="flex-1 rounded-lg border-0 py-2 ps-3 pe-8 text-sm ring-1 ring-inset ring-ink-200">
                                @foreach (\App\Enums\UserRole::cases() as $role)
                                    <option value="{{ $role->value }}" @selected($user->role === $role)>{{ __('admin.users.roles.'.$role->value) }}</option>
                                @endforeach
                            </select>
                            <x-ui.button type="submit" size="sm" variant="secondary">{{ __('common.save') }}</x-ui.button>
                        </form>
                        @if ($user->isSuspended())
                            <form method="POST" action="{{ route('admin.users.unsuspend', $user) }}">@csrf <x-ui.button type="submit" variant="secondary" class="w-full">{{ __('admin.users.unsuspend') }}</x-ui.button></form>
                        @else
                            <form method="POST" action="{{ route('admin.users.suspend', $user) }}">@csrf <x-ui.button type="submit" variant="danger" class="w-full">{{ __('admin.users.suspend') }}</x-ui.button></form>
                        @endif
                    </div>
                </x-ui.card>
            @endunless

            <x-ui.card>
                <h3 class="text-sm font-semibold text-ink-900">{{ __('dashboard.usage.title') }}</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($usage as $allowance)
                        <li class="flex justify-between gap-3"><span class="text-ink-600">{{ $allowance->feature->label() }}</span><span class="tabular-nums text-ink-900">{{ ! $allowance->enabled ? '—' : ($allowance->feature->isBoolean() ? '✓' : ($allowance->isUnlimited() ? $allowance->used.' / ∞' : $allowance->used.' / '.$allowance->limit)) }}</span></li>
                    @endforeach
                </ul>
            </x-ui.card>
        </div>
    </div>
</x-admin.shell>
