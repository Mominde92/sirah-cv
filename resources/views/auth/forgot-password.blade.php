<x-layouts.auth :title="__('auth.forgot.title')" :subtitle="__('auth.forgot.subtitle')">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" novalidate>
        @csrf

        <x-ui.input name="email" type="email" :label="__('auth.fields.email')" autocomplete="email" inputmode="email" dir="ltr" required autofocus />

        <x-ui.button type="submit" class="w-full">{{ __('auth.forgot.submit') }}</x-ui.button>
    </form>

    <p class="mt-8 text-center text-sm">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 font-semibold text-brand-600 hover:text-brand-700">
            <x-heroicon-o-arrow-left class="size-4 flip-rtl" aria-hidden="true" />
            {{ __('auth.forgot.back') }}
        </a>
    </p>
</x-layouts.auth>
