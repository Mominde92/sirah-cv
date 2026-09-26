@props(['title' => null, 'description' => null])

<x-layouts.base :title="$title" :description="$description">
    <header x-data="{ menu: false, scrolled: false }" @scroll.window="scrolled = window.scrollY > 8"
            :class="scrolled ? 'bg-white/85 shadow-[0_1px_0_0_rgb(16_24_40/0.06)] backdrop-blur-lg' : 'bg-transparent'"
            class="fixed inset-x-0 top-0 z-30 transition duration-200">
        <nav class="container-page flex h-16 items-center justify-between gap-6" aria-label="{{ __('common.main_navigation') }}">
            <x-ui.logo />

            <div class="hidden items-center gap-1 lg:flex">
                @foreach (['templates', 'features', 'ats', 'pricing', 'faq'] as $anchor)
                    <a href="{{ route('home') }}#{{ $anchor }}" class="focus-ring rounded-lg px-3 py-2 text-sm font-medium text-ink-600 transition hover:text-ink-900">
                        {{ __('landing.nav.'.$anchor) }}
                    </a>
                @endforeach
            </div>

            <div class="hidden items-center gap-2 lg:flex">
                <x-ui.locale-switcher />
                @auth
                    <x-ui.button :href="route('dashboard')" size="sm" icon-trailing="arrow-right">{{ __('common.nav.dashboard') }}</x-ui.button>
                @else
                    <x-ui.button :href="route('login')" variant="ghost" size="sm">{{ __('auth.login.submit') }}</x-ui.button>
                    <x-ui.button :href="route('register')" size="sm">{{ __('landing.cta.start_free') }}</x-ui.button>
                @endauth
            </div>

            <button type="button" @click="menu = ! menu" :aria-expanded="menu.toString()" aria-controls="mobile-menu"
                    class="focus-ring -me-2 inline-flex size-10 items-center justify-center rounded-lg text-ink-700 lg:hidden">
                <span class="sr-only">{{ __('common.toggle_menu') }}</span>
                <x-heroicon-o-bars-3 class="size-6" x-show="! menu" aria-hidden="true" />
                <x-heroicon-o-x-mark class="size-6" x-show="menu" x-cloak aria-hidden="true" />
            </button>
        </nav>

        <div id="mobile-menu" x-cloak x-show="menu" x-transition class="border-t border-ink-100 bg-white px-4 pb-6 pt-2 shadow-lift lg:hidden">
            @foreach (['templates', 'features', 'ats', 'pricing', 'faq'] as $anchor)
                <a href="{{ route('home') }}#{{ $anchor }}" @click="menu = false" class="block rounded-lg px-3 py-2.5 text-base font-medium text-ink-700 hover:bg-ink-50">
                    {{ __('landing.nav.'.$anchor) }}
                </a>
            @endforeach
            <div class="mt-4 flex flex-col gap-2 border-t border-ink-100 pt-4">
                @auth
                    <x-ui.button :href="route('dashboard')">{{ __('common.nav.dashboard') }}</x-ui.button>
                @else
                    <x-ui.button :href="route('register')">{{ __('landing.cta.start_free') }}</x-ui.button>
                    <x-ui.button :href="route('login')" variant="secondary">{{ __('auth.login.submit') }}</x-ui.button>
                @endauth
                <x-ui.locale-switcher align="start" class="mt-2 self-start" />
            </div>
        </div>
    </header>

    <main id="main">
        @if (session('status'))
            <div class="container-page pt-24">
                <x-ui.session-status />
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="border-t border-ink-100 bg-ink-50/60">
        <div class="container-page grid gap-10 py-14 md:grid-cols-12">
            <div class="md:col-span-5">
                <x-ui.logo />
                <p class="mt-4 max-w-sm text-sm leading-6 text-ink-500">{{ __('landing.footer.about') }}</p>
            </div>

            <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 md:col-span-7">
                <div>
                    <h3 class="text-sm font-semibold text-ink-900">{{ __('landing.footer.product') }}</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        @foreach (['templates', 'features', 'pricing'] as $anchor)
                            <li><a href="{{ route('home') }}#{{ $anchor }}" class="text-ink-500 hover:text-ink-900">{{ __('landing.nav.'.$anchor) }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-ink-900">{{ __('landing.footer.resources') }}</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li><a href="{{ route('home') }}#ats" class="text-ink-500 hover:text-ink-900">{{ __('landing.nav.ats') }}</a></li>
                        <li><a href="{{ route('home') }}#faq" class="text-ink-500 hover:text-ink-900">{{ __('landing.nav.faq') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-ink-900">{{ __('landing.footer.account') }}</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        @auth
                            <li><a href="{{ route('dashboard') }}" class="text-ink-500 hover:text-ink-900">{{ __('common.nav.dashboard') }}</a></li>
                        @else
                            <li><a href="{{ route('register') }}" class="text-ink-500 hover:text-ink-900">{{ __('auth.register.submit') }}</a></li>
                            <li><a href="{{ route('login') }}" class="text-ink-500 hover:text-ink-900">{{ __('auth.login.submit') }}</a></li>
                        @endauth
                    </ul>
                </div>
            </div>
        </div>
        <div class="border-t border-ink-100">
            <div class="container-page flex flex-col items-center justify-between gap-3 py-6 text-xs text-ink-500 sm:flex-row">
                <p>&copy; {{ now()->year }} {{ __('common.brand') }}. {{ __('landing.footer.rights') }}</p>
                <x-ui.locale-switcher />
            </div>
        </div>
    </footer>
</x-layouts.base>
