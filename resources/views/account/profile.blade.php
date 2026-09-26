<x-layouts.app :title="__('account.title')">
    <div class="space-y-6">
        {{-- Profile information --}}
        <x-ui.card padding="p-0">
            <div class="grid gap-8 p-6 md:grid-cols-3 md:p-8">
                <div>
                    <h2 class="text-base font-semibold text-ink-900">{{ __('account.profile.title') }}</h2>
                    <p class="mt-1 text-sm leading-6 text-ink-500">{{ __('account.profile.subtitle') }}</p>
                </div>

                <form method="POST" action="{{ route('user-profile-information.update') }}" enctype="multipart/form-data"
                      x-data="{ preview: null }" class="space-y-5 md:col-span-2" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="flex items-center gap-5">
                        <template x-if="preview">
                            <img :src="preview" alt="" class="size-16 rounded-full object-cover ring-2 ring-white">
                        </template>
                        <div x-show="! preview">
                            <x-ui.avatar :user="$user" :url="$avatarUrl" size="size-16" class="text-lg" />
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <label for="avatar" class="focus-ring inline-flex h-9 cursor-pointer items-center gap-2 rounded-xl bg-white px-3.5 text-sm font-medium text-ink-800 shadow-sm ring-1 ring-inset ring-ink-200 hover:bg-ink-50">
                                <x-heroicon-o-arrow-up-tray class="size-4" aria-hidden="true" />
                                {{ __('account.profile.upload_photo') }}
                            </label>
                            <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only"
                                   @change="const f = $event.target.files[0]; preview = f ? URL.createObjectURL(f) : null">
                            @if ($avatarUrl)
                                <label class="inline-flex items-center gap-2 text-sm text-ink-600">
                                    <input type="checkbox" name="remove_avatar" value="1" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                                    {{ __('account.profile.remove_photo') }}
                                </label>
                            @endif
                        </div>
                    </div>
                    @error('avatar', 'updateProfileInformation')
                        <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-ink-500">{{ __('account.profile.photo_hint') }}</p>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.input name="name" :label="__('auth.fields.name')" :value="$user->name" bag="updateProfileInformation" autocomplete="name" required />
                        <x-ui.input name="email" type="email" :label="__('auth.fields.email')" :value="$user->email" bag="updateProfileInformation" autocomplete="email" dir="ltr" required />
                    </div>

                    @if (! $user->hasVerifiedEmail())
                        <x-ui.alert type="warning">{{ __('account.profile.unverified') }}</x-ui.alert>
                    @endif

                    <x-ui.select name="locale" :label="__('account.profile.language')" bag="updateProfileInformation"
                                 :options="collect($locales)->map(fn ($locale) => $locale['native'])->all()" :selected="$user->locale" class="sm:max-w-xs" />

                    <div class="flex items-center justify-end gap-3 border-t border-ink-100 pt-5">
                        <x-ui.button type="submit">{{ __('common.save') }}</x-ui.button>
                    </div>
                </form>
            </div>
        </x-ui.card>

        {{-- Password --}}
        <x-ui.card padding="p-0">
            <div class="grid gap-8 p-6 md:grid-cols-3 md:p-8">
                <div>
                    <h2 class="text-base font-semibold text-ink-900">{{ __('account.password.title') }}</h2>
                    <p class="mt-1 text-sm leading-6 text-ink-500">{{ __('account.password.subtitle') }}</p>
                </div>

                <form method="POST" action="{{ route('user-password.update') }}" class="space-y-5 md:col-span-2" novalidate>
                    @csrf
                    @method('PUT')

                    <x-ui.input name="current_password" type="password" :label="__('auth.fields.current_password')" bag="updatePassword" autocomplete="current-password" class="sm:max-w-sm" required />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.input name="password" id="new_password" type="password" :label="__('auth.fields.new_password')" :hint="__('auth.password_hint')" bag="updatePassword" autocomplete="new-password" required />
                        <x-ui.input name="password_confirmation" id="new_password_confirmation" type="password" :label="__('auth.fields.password_confirmation')" bag="updatePassword" autocomplete="new-password" required />
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-ink-100 pt-5">
                        <x-ui.button type="submit">{{ __('account.password.submit') }}</x-ui.button>
                    </div>
                </form>
            </div>
        </x-ui.card>

        {{-- Danger zone --}}
        <x-ui.card padding="p-0" class="ring-red-200">
            <div class="grid gap-8 p-6 md:grid-cols-3 md:p-8"
                 x-data="{ confirming: @js($errors->deleteAccount->isNotEmpty()) }">
                <div>
                    <h2 class="text-base font-semibold text-red-700">{{ __('account.delete.title') }}</h2>
                    <p class="mt-1 text-sm leading-6 text-ink-500">{{ __('account.delete.subtitle') }}</p>
                </div>

                <div class="md:col-span-2">
                    <p class="text-sm leading-6 text-ink-600">{{ __('account.delete.warning') }}</p>
                    <x-ui.button variant="danger" class="mt-5" x-on:click="confirming = true">{{ __('account.delete.button') }}</x-ui.button>
                </div>

                <div x-cloak x-show="confirming" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="delete-account-title" @keydown.escape.window="confirming = false">
                    <div x-show="confirming" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="confirming = false"></div>

                    <form x-show="confirming" x-transition method="POST" action="{{ route('profile.destroy') }}" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-lift" novalidate>
                        @csrf
                        @method('DELETE')

                        <div class="flex items-start gap-4">
                            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                                <x-heroicon-o-exclamation-triangle class="size-5" aria-hidden="true" />
                            </span>
                            <div>
                                <h3 id="delete-account-title" class="text-base font-semibold text-ink-900">{{ __('account.delete.confirm_title') }}</h3>
                                <p class="mt-1 text-sm leading-6 text-ink-500">{{ __('account.delete.confirm_body') }}</p>
                            </div>
                        </div>

                        <x-ui.input name="password" id="delete_password" type="password" :label="__('auth.fields.password')" bag="deleteAccount" autocomplete="current-password" class="mt-5" required x-effect="confirming && $nextTick(() => $el.focus())" />

                        <div class="mt-6 flex justify-end gap-3">
                            <x-ui.button variant="secondary" x-on:click="confirming = false">{{ __('common.cancel') }}</x-ui.button>
                            <x-ui.button type="submit" variant="danger">{{ __('account.delete.confirm_button') }}</x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
