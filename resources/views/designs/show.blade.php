<x-layouts.app :title="__('designs.result.title')">
    @if ($processing)
        <x-slot:head><meta http-equiv="refresh" content="3"></x-slot:head>
        <x-ui.card class="mx-auto max-w-xl text-center">
            <span class="mx-auto inline-flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600"><x-heroicon-o-arrow-path class="size-7 animate-spin" aria-hidden="true" /></span>
            <h2 class="mt-5 text-lg font-semibold text-ink-900">{{ __('designs.processing') }}</h2>
        </x-ui.card>
    @elseif ($design->status === \App\Enums\ProcessingStatus::Failed)
        <x-ui.card class="mx-auto max-w-xl text-center">
            <h2 class="text-lg font-semibold text-ink-900">{{ __('designs.failed') }}</h2>
            <p class="mt-2 text-sm text-ink-500">{{ $design->error }}</p>
            <x-ui.button :href="route('designs.create')" class="mt-6">{{ __('imports.failed.retry') }}</x-ui.button>
        </x-ui.card>
    @else
        @php
            $analysis = $design->analysis ?? [];
            $colors = array_filter((array) ($analysis['colors'] ?? []));
            $unknown = __('designs.result.unknown');
        @endphp
        <div class="grid gap-6 lg:grid-cols-5">
            <div class="space-y-6 lg:col-span-2">
                <x-ui.card>
                    <h2 class="text-sm font-semibold text-ink-900">{{ __('designs.result.reference') }}</h2>
                    <div class="mt-4 overflow-hidden rounded-xl bg-ink-50 ring-1 ring-ink-200">
                        @if (str_starts_with($design->mime_type, 'image/'))
                            <img src="{{ route('designs.file', $design) }}" alt="{{ __('designs.result.reference') }}" class="mx-auto max-h-96 object-contain">
                        @else
                            <div class="flex items-center gap-3 p-4 text-sm text-ink-600"><x-heroicon-o-document class="size-6 text-ink-400" /> {{ $design->original_name }}</div>
                        @endif
                    </div>

                    <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg bg-ink-50 p-3"><dt class="text-xs text-ink-500">{{ __('designs.result.layout') }}</dt><dd class="mt-0.5 font-medium text-ink-900">{{ ($analysis['layout'] ?? 'unknown') === 'unknown' ? $unknown : __('editor.design.layouts.'.$analysis['layout']) }}</dd></div>
                        <div class="rounded-lg bg-ink-50 p-3"><dt class="text-xs text-ink-500">{{ __('designs.result.header') }}</dt><dd class="mt-0.5 font-medium text-ink-900">{{ ($analysis['header_style'] ?? 'unknown') === 'unknown' ? $unknown : __('editor.design.headers.'.$analysis['header_style']) }}</dd></div>
                        <div class="rounded-lg bg-ink-50 p-3"><dt class="text-xs text-ink-500">{{ __('designs.result.typography') }}</dt><dd class="mt-0.5 font-medium text-ink-900">{{ __('designs.result.typography_values.'.($analysis['typography'] ?? 'unknown')) }}</dd></div>
                        <div class="rounded-lg bg-ink-50 p-3"><dt class="text-xs text-ink-500">{{ __('designs.result.density') }}</dt><dd class="mt-0.5 font-medium text-ink-900">{{ __('designs.result.density_values.'.($analysis['density'] ?? 'balanced')) }}</dd></div>
                    </dl>

                    @if ($colors !== [])
                        <div class="mt-4">
                            <p class="text-xs text-ink-500">{{ __('designs.result.palette') }}</p>
                            <div class="mt-2 flex gap-2">
                                @foreach ($colors as $name => $color)
                                    <span class="flex flex-col items-center gap-1 text-[11px] text-ink-500"><span class="size-9 rounded-lg ring-1 ring-ink-200" style="background: {{ preg_match('/^#[0-9a-f]{6}$/i', $color) ? $color : '#ffffff' }}"></span>{{ __('designs.result.colors.'.$name) }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($design->mime_type === 'application/pdf')
                        <p class="mt-4 text-xs leading-5 text-ink-500">{{ __('designs.result.pdf_note') }}</p>
                    @endif
                </x-ui.card>
            </div>

            <div class="space-y-6 lg:col-span-3">
                <x-ui.card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-ink-900">{{ __('designs.result.suggestion') }}</h2>
                            <p class="mt-1 text-sm text-ink-500">{{ __('designs.result.suggestion_body', ['template' => $design->suggestedTemplate?->name]) }}</p>
                        </div>
                        @if ($design->similarity !== null)
                            <x-ui.badge color="brand">{{ __('designs.result.closeness', ['percent' => $design->similarity]) }}</x-ui.badge>
                        @endif
                    </div>

                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        <div class="rounded-xl bg-ink-50 p-3 ring-1 ring-ink-200">
                            <x-resume.thumbnail :src="route('designs.preview', ['design' => $design, 'lang' => app()->getLocale()])" :label="__('designs.result.suggestion')" class="rounded shadow-soft" />
                        </div>

                        <form method="POST" action="{{ route('designs.apply', $design) }}" x-data="{ target: 'new' }" class="space-y-4">
                            @csrf
                            <fieldset class="space-y-2">
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl p-3 ring-1" :class="target === 'new' ? 'ring-2 ring-brand-500 bg-brand-50/40' : 'ring-ink-200'">
                                    <input type="radio" name="target" value="new" x-model="target" class="mt-0.5 text-brand-600 focus:ring-brand-500">
                                    <span class="text-sm"><span class="block font-semibold text-ink-900">{{ __('designs.apply.new') }}</span><span class="text-ink-500">{{ __('designs.apply.new_hint') }}</span></span>
                                </label>
                                @if ($resumes->isNotEmpty())
                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl p-3 ring-1" :class="target === 'existing' ? 'ring-2 ring-brand-500 bg-brand-50/40' : 'ring-ink-200'">
                                        <input type="radio" name="target" value="existing" x-model="target" class="mt-0.5 text-brand-600 focus:ring-brand-500">
                                        <span class="text-sm"><span class="block font-semibold text-ink-900">{{ __('designs.apply.existing') }}</span><span class="text-ink-500">{{ __('designs.apply.existing_hint') }}</span></span>
                                    </label>
                                @endif
                            </fieldset>

                            <div x-show="target === 'new'">
                                <x-ui.select name="language" :label="__('resumes.fields.language')" :options="collect($locales)->map(fn ($l) => $l['native'])->all()" :selected="auth()->user()->locale" />
                            </div>
                            @if ($resumes->isNotEmpty())
                                <div x-show="target === 'existing'" x-cloak>
                                    <x-ui.select name="resume" :label="__('designs.apply.choose_resume')" :options="$resumes->pluck('title', 'uuid')->all()" />
                                </div>
                            @endif

                            <x-ui.button type="submit" class="w-full" icon="sparkles">{{ __('designs.apply.submit') }}</x-ui.button>
                            <p class="text-xs leading-5 text-ink-500">{{ __('designs.originality') }}</p>
                        </form>
                    </div>
                </x-ui.card>

                <form method="POST" action="{{ route('designs.destroy', $design) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-ink-500 underline-offset-2 hover:text-red-600 hover:underline">{{ __('designs.delete') }}</button>
                </form>
            </div>
        </div>
    @endif
</x-layouts.app>
