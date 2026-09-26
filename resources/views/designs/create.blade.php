<x-layouts.app :title="__('designs.title')">
    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <h2 class="text-lg font-semibold text-ink-900">{{ __('designs.heading') }}</h2>
            <p class="mt-1 max-w-xl text-sm leading-6 text-ink-500">{{ __('designs.subtitle') }}</p>

            @if (! $allowance->allows())
                <x-ui.alert type="warning" class="mt-6">
                    {{ $allowance->enabled ? __('billing.limit_reached', ['feature' => \App\Enums\Feature::DesignReference->label(), 'limit' => $allowance->limit]) : __('billing.feature_unavailable', ['feature' => \App\Enums\Feature::DesignReference->label()]) }}
                    <a href="{{ route('billing.index') }}" class="ms-1 font-semibold underline underline-offset-2">{{ __('billing.see_plans') }}</a>
                </x-ui.alert>
            @endif

            <form method="POST" action="{{ route('designs.store') }}" enctype="multipart/form-data" class="mt-6" x-data="{ preview: null, name: null }">
                @csrf
                <label for="file" class="flex cursor-pointer flex-col items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-ink-200 bg-ink-50/50 px-6 py-10 text-center transition hover:border-brand-300">
                    <template x-if="preview"><img :src="preview" alt="" class="max-h-72 rounded-lg shadow-soft"></template>
                    <template x-if="! preview">
                        <span class="flex flex-col items-center">
                            <span class="inline-flex size-14 items-center justify-center rounded-2xl bg-white text-brand-600 shadow-soft ring-1 ring-ink-200"><x-heroicon-o-photo class="size-7" aria-hidden="true" /></span>
                            <span class="mt-4 text-sm font-semibold text-ink-900" x-text="name ?? @js(__('designs.drop'))"></span>
                            <span class="mt-1 text-xs text-ink-500">{{ __('designs.formats', ['size' => (int) (config('cv.uploads.max_kilobytes') / 1024)]) }}</span>
                        </span>
                    </template>
                    <input id="file" name="file" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="sr-only" required
                           @change="const f = $event.target.files[0]; name = f?.name; preview = f && f.type.startsWith('image/') ? URL.createObjectURL(f) : null">
                </label>
                @error('file') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror

                <x-ui.alert type="info" class="mt-6">{{ __('designs.originality') }}</x-ui.alert>

                <div class="mt-6 flex justify-end">
                    <x-ui.button type="submit" icon="sparkles" :disabled="! $allowance->allows()">{{ __('designs.submit') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card>
                <h3 class="text-sm font-semibold text-ink-900">{{ __('designs.detects.title') }}</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-ink-600">
                    @foreach (['layout', 'colors', 'header', 'typography', 'spacing'] as $item)
                        <li class="flex gap-2"><x-heroicon-s-check class="mt-0.5 size-4 shrink-0 text-emerald-500" aria-hidden="true" /> {{ __('designs.detects.'.$item) }}</li>
                    @endforeach
                </ul>
            </x-ui.card>

            @if ($recent->isNotEmpty())
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink-900">{{ __('designs.recent') }}</h3>
                    <ul class="mt-3 divide-y divide-ink-100">
                        @foreach ($recent as $item)
                            <li class="flex items-center justify-between gap-3 py-2.5">
                                <a href="{{ route('designs.show', $item) }}" class="min-w-0 truncate text-sm text-ink-800 hover:text-brand-700">{{ $item->original_name }}</a>
                                <x-ui.badge :color="$item->status->value === 'completed' ? 'emerald' : ($item->status->value === 'failed' ? 'amber' : 'brand')">{{ __('imports.status.'.$item->status->value) }}</x-ui.badge>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif
        </div>
    </div>
</x-layouts.app>
