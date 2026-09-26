<x-admin.shell :title="__('admin.nav.settings')">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-3xl space-y-6">
        @csrf
        @method('PUT')

        <x-ui.card class="space-y-5">
            <h2 class="text-sm font-semibold text-ink-900">{{ __('admin.settings.general') }}</h2>
            <x-ui.input name="general__support_email" type="email" :label="__('admin.settings.support_email')" :value="$settings->get('general.support_email')" dir="ltr" />
            <x-ui.input name="general__announcement_en" :label="__('admin.settings.announcement_en')" :value="$settings->get('general.announcement_en')" :hint="__('admin.settings.announcement_hint')" />
            <x-ui.input name="general__announcement_ar" :label="__('admin.settings.announcement_ar')" :value="$settings->get('general.announcement_ar')" dir="rtl" />
            <label class="flex items-center justify-between gap-3 text-sm">
                <span><span class="block font-medium text-ink-800">{{ __('admin.settings.registration_open') }}</span><span class="text-xs text-ink-500">{{ __('admin.settings.registration_hint') }}</span></span>
                <input type="hidden" name="general__registration_open" value="0">
                <input type="checkbox" name="general__registration_open" value="1" @checked($settings->get('general.registration_open')) class="rounded border-ink-300 text-brand-600">
            </label>
        </x-ui.card>

        <x-ui.card class="space-y-5">
            <h2 class="text-sm font-semibold text-ink-900">{{ __('admin.settings.ai') }}</h2>
            <label class="flex items-center justify-between gap-3 text-sm">
                <span><span class="block font-medium text-ink-800">{{ __('admin.settings.ai_enabled') }}</span><span class="text-xs text-ink-500">{{ __('admin.settings.ai_hint') }}</span></span>
                <input type="hidden" name="ai__enabled" value="0">
                <input type="checkbox" name="ai__enabled" value="1" @checked($settings->get('ai.enabled')) class="rounded border-ink-300 text-brand-600">
            </label>
        </x-ui.card>

        <div class="flex justify-end"><x-ui.button type="submit">{{ __('common.save') }}</x-ui.button></div>
    </form>
</x-admin.shell>
