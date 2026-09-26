<x-layouts.auth :title="__('auth.confirm.title')" :subtitle="__('auth.confirm.subtitle')">
    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-5" novalidate>
        @csrf

        <x-ui.input name="password" type="password" :label="__('auth.fields.password')" autocomplete="current-password" required autofocus />

        <x-ui.button type="submit" class="w-full">{{ __('auth.confirm.submit') }}</x-ui.button>
    </form>
</x-layouts.auth>
