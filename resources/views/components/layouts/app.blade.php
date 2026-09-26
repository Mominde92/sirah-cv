@props(['title', 'heading' => null])

@inject('avatars', 'App\Services\Account\AvatarService')

@php
    $user = auth()->user();
    $avatarUrl = $avatars->url($user);
    $navigation = [
        ['route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'squares-2x2', 'label' => __('common.nav.dashboard')],
        ['route' => 'resumes.index', 'active' => 'resumes.*', 'icon' => 'document-text', 'label' => __('common.nav.resumes')],
        ['route' => 'templates.index', 'active' => 'templates.*', 'icon' => 'swatch', 'label' => __('common.nav.templates')],
        ['route' => 'imports.create', 'active' => 'imports.*', 'icon' => 'arrow-up-tray', 'label' => __('common.nav.import')],
        ['route' => 'designs.create', 'active' => 'designs.*', 'icon' => 'photo', 'label' => __('common.nav.designs')],
        ['route' => 'billing.index', 'active' => 'billing.*', 'icon' => 'credit-card', 'label' => __('common.nav.billing')],
        ['route' => 'profile.edit', 'active' => 'profile.*', 'icon' => 'user-circle', 'label' => __('common.nav.profile')],
    ];

    if ($user->can('access-admin')) {
        $navigation[] = ['route' => 'admin.overview', 'active' => 'admin.*', 'icon' => 'shield-check', 'label' => __('common.nav.admin')];
    }
@endphp

<x-layouts.base :title="$title" body-class="bg-ink-50 text-ink-800">
    @isset($head)
        <x-slot:head>{{ $head }}</x-slot:head>
    @endisset

    <div x-data="{ sidebar: false }" class="min-h-full">
        {{-- Mobile sidebar --}}
        <div x-cloak x-show="sidebar" class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true">
            <div x-show="sidebar" x-transition.opacity class="absolute inset-0 bg-ink-950/40" @click="sidebar = false"></div>
            <div x-show="sidebar"
                 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="ltr:-translate-x-full rtl:translate-x-full"
                 x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="ltr:-translate-x-full rtl:translate-x-full"
                 class="absolute inset-y-0 start-0 flex w-72 flex-col bg-white shadow-lift">
                @include('components.layouts.partials.app-sidebar', ['navigation' => $navigation])
            </div>
        </div>

        {{-- Desktop sidebar --}}
        <aside class="fixed inset-y-0 start-0 z-30 hidden w-64 flex-col border-e border-ink-200/70 bg-white lg:flex">
            @include('components.layouts.partials.app-sidebar', ['navigation' => $navigation])
        </aside>

        <div class="lg:ps-64">
            <header class="sticky top-0 z-20 border-b border-ink-200/70 bg-white/85 backdrop-blur-lg">
                <div class="flex h-16 items-center gap-4 px-4 sm:px-6 lg:px-8">
                    <button type="button" @click="sidebar = true" class="focus-ring -ms-2 inline-flex size-10 items-center justify-center rounded-lg text-ink-600 lg:hidden">
                        <span class="sr-only">{{ __('common.toggle_menu') }}</span>
                        <x-heroicon-o-bars-3 class="size-6" aria-hidden="true" />
                    </button>

                    <h1 class="min-w-0 flex-1 truncate text-lg font-semibold text-ink-900">{{ $heading ?? $title }}</h1>

                    <x-ui.locale-switcher />

                    <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                        <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-haspopup="true" class="focus-ring flex items-center gap-2 rounded-full">
                            <x-ui.avatar :user="$user" :url="$avatarUrl" />
                            <span class="sr-only">{{ __('common.open_user_menu') }}</span>
                        </button>

                        <div x-cloak x-show="open" x-transition.origin.top class="absolute end-0 top-full z-40 mt-2 w-64 rounded-xl bg-white p-1.5 shadow-lift ring-1 ring-ink-200">
                            <div class="px-3 py-2.5">
                                <p class="truncate text-sm font-semibold text-ink-900">{{ $user->name }}</p>
                                <p class="truncate text-xs text-ink-500">{{ $user->email }}</p>
                            </div>
                            <div class="my-1 border-t border-ink-100"></div>
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-ink-700 hover:bg-ink-50">
                                <x-heroicon-o-user-circle class="size-4 text-ink-400" aria-hidden="true" />
                                {{ __('common.nav.profile') }}
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-start text-sm text-ink-700 hover:bg-ink-50">
                                    <x-heroicon-o-arrow-right-start-on-rectangle class="size-4 text-ink-400 flip-rtl" aria-hidden="true" />
                                    {{ __('common.nav.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main id="main" class="px-4 py-8 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-6xl space-y-4">
                    @php($announcement = app(\App\Services\Admin\SettingsService::class)->get('general.announcement_'.app()->getLocale()))
                    @if (filled($announcement))
                        <x-ui.alert type="info">{{ $announcement }}</x-ui.alert>
                    @endif
                    <x-ui.session-status />

                    @if (session('upgrade'))
                        <x-ui.alert type="warning">
                            <p>{{ session('upgrade.message') }}</p>
                            <a href="{{ route('billing.index') }}" class="mt-1 inline-block font-semibold underline underline-offset-2">{{ __('billing.see_plans') }}</a>
                        </x-ui.alert>
                    @endif
                </div>

                <div class="mx-auto max-w-6xl">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</x-layouts.base>
