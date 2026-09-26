<x-layouts.auth :title="__('auth.reset.title')" :subtitle="__('auth.reset.subtitle')">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-ui.input name="email" type="email" :label="__('auth.fields.email')" :value="$request->query('email')" autocomplete="email" dir="ltr" required />

        <x-ui.input name="password" type="password" :label="__('auth.fields.new_password')" :hint="__('auth.password_hint')" autocomplete="new-password" required autofocus />

        <x-ui.input name="password_confirmation" type="password" :label="__('auth.fields.password_confirmation')" autocomplete="new-password" required />

        <x-ui.button type="submit" class="w-full">{{ __('auth.reset.submit') }}</x-ui.button>
    </form>
</x-layouts.auth>
