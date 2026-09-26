@props(['title'])

@php
    $items = [
        'admin.overview' => ['chart-bar', 'overview'],
        'admin.users.index' => ['users', 'users'],
        'admin.resumes.index' => ['document-text', 'resumes'],
        'admin.templates.index' => ['swatch', 'templates'],
        'admin.plans.index' => ['rectangle-stack', 'plans'],
        'admin.subscriptions.index' => ['arrow-path-rounded-square', 'subscriptions'],
        'admin.payments.index' => ['banknotes', 'payments'],
        'admin.ai-usage.index' => ['sparkles', 'ai_usage'],
        'admin.downloads.index' => ['arrow-down-tray', 'downloads'],
        'admin.reports.index' => ['presentation-chart-line', 'reports'],
        'admin.settings.edit' => ['cog-6-tooth', 'settings'],
    ];
@endphp

<x-layouts.app :title="$title" :heading="__('admin.title').' · '.$title">
    <nav class="mb-6" aria-label="{{ __('admin.title') }}">
        <div class="flex flex-wrap gap-1 rounded-2xl bg-white p-1.5 shadow-soft ring-1 ring-ink-200/70">
            @foreach ($items as $route => [$icon, $key])
                @php($active = request()->routeIs(\Illuminate\Support\Str::beforeLast($route, '.').'.*') || request()->routeIs($route))
                <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                   @class(['inline-flex shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium whitespace-nowrap transition', 'bg-ink-900 text-white' => $active, 'text-ink-600 hover:bg-ink-50 hover:text-ink-900' => ! $active])>
                    <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-4" aria-hidden="true" />
                    {{ __('admin.nav.'.$key) }}
                </a>
            @endforeach
        </div>
    </nav>

    {{ $slot }}
</x-layouts.app>
