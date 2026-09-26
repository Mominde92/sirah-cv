<x-layouts.auth :title="__('auth.register.title')" :subtitle="__('auth.register.subtitle')">
    <form method="POST" action="{{ route('register.store') }}" class="space-y-5" novalidate>
        @csrf

        <x-ui.input name="name" :label="__('auth.fields.name')" autocomplete="name" required autofocus />

        <x-ui.input name="email" type="email" :label="__('auth.fields.email')" autocomplete="email" inputmode="email" dir="ltr" required />

        <x-ui.input name="password" type="password" :label="__('auth.fields.password')" :hint="__('auth.password_hint')" autocomplete="new-password" required />

        <x-ui.input name="password_confirmation" type="password" :label="__('auth.fields.password_confirmation')" autocomplete="new-password" required />

        <x-ui.checkbox name="terms" required>
            {{ __('auth.register.terms') }}
        </x-ui.checkbox>

        <x-ui.button type="submit" class="w-full">{{ __('auth.register.submit') }}</x-ui.button>
    </form>

    <p class="mt-8 text-center text-sm text-ink-500">
        {{ __('auth.register.have_account') }}
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">{{ __('auth.login.submit') }}</a>
    </p>
</x-layouts.auth>
