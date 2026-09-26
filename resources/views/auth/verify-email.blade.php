<x-layouts.auth :title="__('auth.verify.title')" :subtitle="__('auth.verify.subtitle', ['email' => auth()->user()->email])">
    <div class="flex items-center justify-center rounded-2xl bg-brand-50 py-10">
        <span class="inline-flex size-16 items-center justify-center rounded-2xl bg-white shadow-soft ring-1 ring-brand-100">
            <x-heroicon-o-envelope-open class="size-8 text-brand-600" aria-hidden="true" />
        </span>
    </div>

    <div class="mt-8 space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-ui.button type="submit" class="w-full">{{ __('auth.verify.resend') }}</x-ui.button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.button type="submit" variant="ghost" class="w-full">{{ __('common.nav.logout') }}</x-ui.button>
        </form>
    </div>
</x-layouts.auth>
