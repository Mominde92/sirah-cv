<x-layouts.app :title="__('imports.title')">
    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <div class="max-w-xl">
                <h2 class="text-lg font-semibold text-ink-900">{{ __('imports.heading') }}</h2>
                <p class="mt-1 text-sm leading-6 text-ink-500">{{ __('imports.subtitle') }}</p>
            </div>

            @if (! $allowance->allows())
                <x-ui.alert type="warning" class="mt-6">
                    {{ $allowance->enabled ? __('billing.limit_reached', ['feature' => \App\Enums\Feature::CvImport->label(), 'limit' => $allowance->limit]) : __('billing.feature_unavailable', ['feature' => \App\Enums\Feature::CvImport->label()]) }}
                    <a href="{{ route('billing.index') }}" class="ms-1 font-semibold underline underline-offset-2">{{ __('billing.see_plans') }}</a>
                </x-ui.alert>
            @endif

            <form method="POST" action="{{ route('imports.store') }}" enctype="multipart/form-data" class="mt-6"
                  x-data="{ name: null, dragging: false }">
                @csrf
                <label for="file" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                       @drop.prevent="dragging = false; $refs.input.files = $event.dataTransfer.files; name = $event.dataTransfer.files[0]?.name"
                       :class="dragging ? 'border-brand-400 bg-brand-50/60' : 'border-ink-200 bg-ink-50/50'"
                       class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-14 text-center transition hover:border-brand-300">
                    <span class="inline-flex size-14 items-center justify-center rounded-2xl bg-white text-brand-600 shadow-soft ring-1 ring-ink-200"><x-heroicon-o-document-arrow-up class="size-7" aria-hidden="true" /></span>
                    <span class="mt-4 text-sm font-semibold text-ink-900" x-text="name ?? @js(__('imports.drop'))"></span>
                    <span class="mt-1 text-xs text-ink-500">{{ __('imports.formats', ['size' => (int) (config('cv.uploads.max_kilobytes') / 1024)]) }}</span>
                    <input x-ref="input" id="file" name="file" type="file" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="sr-only" @change="name = $event.target.files[0]?.name" required>
                </label>
                @error('file') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror

                <div class="mt-6 flex items-center justify-between gap-4">
                    <p class="flex items-start gap-2 text-xs leading-5 text-ink-500"><x-heroicon-o-lock-closed class="mt-0.5 size-4 shrink-0" aria-hidden="true" /> {{ __('imports.privacy') }}</p>
                    <x-ui.button type="submit" icon="sparkles" :disabled="! $allowance->allows()">{{ __('imports.submit') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card>
                <h3 class="text-sm font-semibold text-ink-900">{{ __('imports.how.title') }}</h3>
                <ol class="mt-4 space-y-3 text-sm text-ink-600">
                    @foreach (['upload', 'extract', 'review', 'edit'] as $index => $step)
                        <li class="flex gap-3"><span class="inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">{{ $index + 1 }}</span> {{ __('imports.how.'.$step) }}</li>
                    @endforeach
                </ol>
            </x-ui.card>

            @if ($recent->isNotEmpty())
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink-900">{{ __('imports.recent') }}</h3>
                    <ul class="mt-3 divide-y divide-ink-100">
                        @foreach ($recent as $item)
                            <li class="flex items-center justify-between gap-3 py-2.5">
                                <a href="{{ route('imports.show', $item) }}" class="min-w-0 truncate text-sm text-ink-800 hover:text-brand-700">{{ $item->original_name }}</a>
                                <x-ui.badge :color="match ($item->status->value) { 'completed' => 'emerald', 'failed' => 'amber', default => 'brand' }">{{ __('imports.status.'.$item->status->value) }}</x-ui.badge>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif
        </div>
    </div>
</x-layouts.app>
