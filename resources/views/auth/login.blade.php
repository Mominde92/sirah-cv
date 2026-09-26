<x-layouts.auth :title="__('auth.login.title')" :subtitle="__('auth.login.subtitle')">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-5" novalidate>
        @csrf

        <x-ui.input name="email" type="email" :label="__('auth.fields.email')" autocomplete="email" inputmode="email" dir="ltr" required autofocus />

        <x-ui.input name="password" type="password" :label="__('auth.fields.password')" autocomplete="current-password" required>
            <x-slot:label-aside>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('auth.login.forgot') }}</a>
            </x-slot:label-aside>
        </x-ui.input>

        <x-ui.checkbox name="remember">{{ __('auth.login.remember') }}</x-ui.checkbox>

        <x-ui.button type="submit" class="w-full">{{ __('auth.login.submit') }}</x-ui.button>
    </form>

    <p class="mt-8 text-center text-sm text-ink-500">
        {{ __('auth.login.no_account') }}
        <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-700">{{ __('auth.login.create_account') }}</a>
    </p>
</x-layouts.auth>
