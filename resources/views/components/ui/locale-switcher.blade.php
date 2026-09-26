@props(['align' => 'end', 'dark' => false])

@inject('locales', 'App\Services\Localization\LocaleService')

<div x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false" {{ $attributes->class('relative') }}>
    <button
        type="button"
        @click="open = ! open"
        :aria-expanded="open.toString()"
        aria-haspopup="true"
        class="focus-ring inline-flex h-9 items-center gap-1.5 rounded-lg px-2.5 text-sm font-medium transition {{ $dark ? 'text-white/80 hover:bg-white/10 hover:text-white' : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900' }}"
    >
        <x-heroicon-o-language class="size-4" aria-hidden="true" />
        <span>{{ $locales->nativeName(app()->getLocale()) }}</span>
        <x-heroicon-o-chevron-down class="size-3.5 opacity-60" aria-hidden="true" />
        <span class="sr-only">{{ __('common.change_language') }}</span>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top
        class="absolute top-full z-40 mt-2 w-44 overflow-hidden rounded-xl bg-white p-1 shadow-lift ring-1 ring-ink-200 {{ $align === 'start' ? 'start-0' : 'end-0' }}"
    >
        @foreach ($locales->all() as $code => $locale)
            <form method="POST" action="{{ route('locale.update', $code) }}">
                @csrf
                <button
                    type="submit"
                    lang="{{ $code }}"
                    @class([
                        'flex w-full items-center justify-between rounded-lg px-3 py-2 text-start text-sm transition',
                        'bg-brand-50 font-medium text-brand-700' => $code === app()->getLocale(),
                        'text-ink-700 hover:bg-ink-50' => $code !== app()->getLocale(),
                    ])
                >
                    {{ $locale['native'] }}
                    @if ($code === app()->getLocale())
                        <x-heroicon-o-check class="size-4" aria-hidden="true" />
                    @endif
                </button>
            </form>
        @endforeach
    </div>
</div>
