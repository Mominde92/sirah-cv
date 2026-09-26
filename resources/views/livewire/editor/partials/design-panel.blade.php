@php
    $control = 'block w-full rounded-lg border-0 py-2 ps-3 pe-8 text-sm ring-1 ring-inset ring-ink-200 focus:ring-2 focus:ring-brand-500 disabled:bg-ink-50 disabled:text-ink-400';
    $lock = fn (string $key) => ! $canAdvanced && in_array($key, \App\Data\StyleSettings::ADVANCED, true);
@endphp

<div x-data="{ tab: 'design' }" class="flex h-full flex-col">
    <div class="flex shrink-0 border-b border-ink-200 px-2" role="tablist">
        @foreach (['design' => __('editor.design.tab'), 'history' => __('editor.history.tab')] as $key => $label)
            <button type="button" role="tab" @click="tab = @js($key)" :aria-selected="(tab === @js($key)).toString()"
                    :class="tab === @js($key) ? 'border-brand-600 text-brand-700' : 'border-transparent text-ink-500 hover:text-ink-800'"
                    class="-mb-px border-b-2 px-3 py-3 text-sm font-medium">{{ $label }}</button>
        @endforeach
    </div>

    {{-- Design --}}
    <div x-show="tab === 'design'" class="space-y-6 p-4">
        <section class="rounded-2xl bg-ink-50 p-3 ring-1 ring-ink-200/70">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs text-ink-500">{{ __('editor.design.template') }}</p>
                    <p class="truncate text-sm font-semibold text-ink-900">{{ $resume->template->name }}</p>
                </div>
                <x-template.badge :status="$resume->template->ats_status" />
            </div>
            <button type="button" @click="templates = true" class="mt-3 w-full rounded-lg bg-white py-2 text-sm font-medium text-ink-700 ring-1 ring-ink-200 hover:bg-ink-50">{{ __('editor.change_template') }}</button>
            @if (! $resume->template->ats_status->isAtsFriendly())
                <p class="mt-2 text-xs leading-5 text-ink-500">{{ __('editor.design.creative_note') }}</p>
            @endif
        </section>

        {{-- Typography --}}
        <section class="space-y-3">
            <h3 class="text-xs font-semibold tracking-wide text-ink-500 uppercase">{{ __('editor.design.typography') }}</h3>
            @if ($rtl)
                <div>
                    <label for="font_arabic" class="mb-1 block text-xs font-medium text-ink-600">{{ __('editor.design.font_arabic') }}</label>
                    <select id="font_arabic" wire:model.live="styling.font_arabic" class="{{ $control }}">
                        @foreach ($arabicFonts as $key => $label) <option value="{{ $key }}">{{ $label }}</option> @endforeach
                    </select>
                </div>
            @endif
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="font_body" class="mb-1 block text-xs font-medium text-ink-600">{{ $rtl ? __('editor.design.font_latin') : __('editor.design.font_body') }}</label>
                    <select id="font_body" wire:model.live="styling.font_body" class="{{ $control }}">
                        @foreach ($latinFonts as $key => $label) <option value="{{ $key }}">{{ $label }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label for="font_heading" class="mb-1 block text-xs font-medium text-ink-600">{{ __('editor.design.font_heading') }}</label>
                    <select id="font_heading" wire:model.live="styling.font_heading" class="{{ $control }}" @disabled($rtl)>
                        @foreach ($latinFonts as $key => $label) <option value="{{ $key }}">{{ $label }}</option> @endforeach
                    </select>
                </div>
            </div>

            @foreach (['font_size' => ['pt', 0.5], 'heading_scale' => ['×', 0.05], 'line_height' => ['', 0.05]] as $key => [$unit, $step])
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs">
                        <label for="{{ $key }}" class="font-medium text-ink-600">{{ __('editor.design.'.$key) }} @if ($lock($key)) <x-heroicon-s-lock-closed class="inline size-3 text-ink-400" /> @endif</label>
                        <span class="tabular-nums text-ink-500">{{ $styling[$key] }}{{ $unit }}</span>
                    </div>
                    <input id="{{ $key }}" type="range" min="{{ $ranges[$key][0] }}" max="{{ $ranges[$key][1] }}" step="{{ $step }}"
                           wire:model.live.debounce.250ms="styling.{{ $key }}" @disabled($lock($key)) class="w-full accent-brand-600 disabled:opacity-40">
                </div>
            @endforeach
        </section>

        {{-- Colours --}}
        <section class="space-y-3">
            <h3 class="text-xs font-semibold tracking-wide text-ink-500 uppercase">{{ __('editor.design.colors') }}</h3>
            <div class="grid grid-cols-2 gap-3">
                @foreach (['color_primary', 'color_accent', 'color_text', 'color_secondary'] as $key)
                    <label class="flex items-center gap-2 rounded-lg p-2 ring-1 ring-ink-200 @if ($lock($key)) opacity-60 @endif">
                        <input type="color" wire:model.live.debounce.300ms="styling.{{ $key }}" @disabled($lock($key)) class="size-8 shrink-0 cursor-pointer rounded-md border-0 bg-transparent p-0 disabled:cursor-not-allowed">
                        <span class="min-w-0 text-xs">
                            <span class="block font-medium text-ink-700">{{ __('editor.design.'.$key) }}</span>
                            <span class="block font-mono text-ink-400 uppercase" dir="ltr">{{ $styling[$key] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-2" aria-label="{{ __('editor.design.presets') }}">
                @foreach (['#1f2533', '#1e3a5f', '#3a48e3', '#0f766e', '#15803d', '#9f1239', '#6d28d9', '#b45309'] as $preset)
                    <button type="button" wire:click="$set('styling.color_primary', @js($preset))" class="size-7 rounded-full ring-2 ring-white shadow-soft outline-1 outline-ink-200 hover:scale-110 @if ($styling['color_primary'] === $preset) outline-2 outline-brand-600 @endif" style="background: {{ $preset }}" title="{{ $preset }}"></button>
                @endforeach
            </div>
        </section>

        {{-- Layout --}}
        <section class="space-y-3">
            <h3 class="text-xs font-semibold tracking-wide text-ink-500 uppercase">{{ __('editor.design.layout') }}</h3>

            @if (count($layouts) > 1)
                <div>
                    <label for="layout" class="mb-1 block text-xs font-medium text-ink-600">{{ __('editor.design.columns') }} @if ($lock('layout')) <x-heroicon-s-lock-closed class="inline size-3 text-ink-400" /> @endif</label>
                    <select id="layout" wire:model.live="styling.layout" class="{{ $control }}" @disabled($lock('layout'))>
                        @foreach ($layouts as $layout) <option value="{{ $layout }}">{{ __('editor.design.layouts.'.$layout) }}</option> @endforeach
                    </select>
                </div>
            @endif

            @if (count($headerStyles) > 1)
                <div>
                    <label for="header_style" class="mb-1 block text-xs font-medium text-ink-600">{{ __('editor.design.header_style') }} @if ($lock('header_style')) <x-heroicon-s-lock-closed class="inline size-3 text-ink-400" /> @endif</label>
                    <select id="header_style" wire:model.live="styling.header_style" class="{{ $control }}" @disabled($lock('header_style'))>
                        @foreach ($headerStyles as $style) <option value="{{ $style }}">{{ __('editor.design.headers.'.$style) }}</option> @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="section_title_style" class="mb-1 block text-xs font-medium text-ink-600">{{ __('editor.design.section_title_style') }}</label>
                <select id="section_title_style" wire:model.live="styling.section_title_style" class="{{ $control }}">
                    @foreach ($titleStyles as $style) <option value="{{ $style }}">{{ __('editor.design.title_styles.'.$style) }}</option> @endforeach
                </select>
            </div>

            @foreach (['page_margin_mm' => ['mm', 1], 'section_spacing' => ['pt', 1], 'item_spacing' => ['pt', 1]] as $key => [$unit, $step])
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs">
                        <label for="{{ $key }}" class="font-medium text-ink-600">{{ __('editor.design.'.$key) }} @if ($lock($key)) <x-heroicon-s-lock-closed class="inline size-3 text-ink-400" /> @endif</label>
                        <span class="tabular-nums text-ink-500">{{ $styling[$key] }}{{ $unit }}</span>
                    </div>
                    <input id="{{ $key }}" type="range" min="{{ $ranges[$key][0] }}" max="{{ $ranges[$key][1] }}" step="{{ $step }}"
                           wire:model.live.debounce.250ms="styling.{{ $key }}" @disabled($lock($key)) class="w-full accent-brand-600 disabled:opacity-40">
                </div>
            @endforeach

            @if ($photoAllowed)
                <label class="flex items-center justify-between gap-3 rounded-lg p-3 ring-1 ring-ink-200">
                    <span class="text-sm text-ink-700">{{ __('editor.design.show_photo') }}</span>
                    <input type="checkbox" wire:model.live="styling.show_photo" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                </label>
            @endif
        </section>

        @unless ($canAdvanced)
            <div class="rounded-xl bg-gradient-to-br from-brand-50 to-violet-50 p-3 text-xs leading-5 text-ink-700 ring-1 ring-brand-100">
                <x-heroicon-s-lock-closed class="me-1 inline size-3.5 text-brand-600" /> {{ __('editor.design.advanced_locked') }}
                <a href="{{ route('billing.index') }}" class="font-semibold text-brand-700 underline underline-offset-2">{{ __('billing.see_plans') }}</a>
            </div>
        @endunless

        <button type="button" wire:click="resetStyling" wire:confirm="{{ __('editor.design.reset_confirm') }}" class="w-full rounded-lg py-2 text-sm font-medium text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50">
            {{ __('editor.design.reset') }}
        </button>
    </div>

    {{-- History --}}
    <div x-cloak x-show="tab === 'history'" class="space-y-4 p-4">
        @if ($canVersions)
            <form wire:submit="saveVersion" class="space-y-2">
                <label for="version-label" class="block text-xs font-medium text-ink-600">{{ __('editor.history.label') }}</label>
                <div class="flex gap-2">
                    <input id="version-label" type="text" wire:model="versionLabel" maxlength="120" placeholder="{{ __('editor.history.placeholder') }}" class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-ink-200 focus:ring-2 focus:ring-brand-500">
                    <button type="submit" class="shrink-0 rounded-lg bg-ink-900 px-3 text-sm font-medium text-white hover:bg-ink-800">{{ __('editor.history.save') }}</button>
                </div>
            </form>

            <ul class="divide-y divide-ink-100 rounded-xl ring-1 ring-ink-200">
                @forelse ($versions as $snapshot)
                    <li class="flex items-center justify-between gap-3 px-3 py-2.5">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-ink-800">{{ $snapshot->label ?: __('editor.history.version', ['number' => $snapshot->version]) }}</p>
                            <p class="text-xs text-ink-500">{{ $snapshot->created_at->diffForHumans() }}</p>
                        </div>
                        <button type="button" wire:click="restoreVersion({{ $snapshot->id }})" wire:confirm="{{ __('editor.history.restore_confirm') }}" class="shrink-0 rounded-md px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50">{{ __('editor.history.restore') }}</button>
                    </li>
                @empty
                    <li class="px-3 py-6 text-center text-sm text-ink-500">{{ __('editor.history.empty') }}</li>
                @endforelse
            </ul>
        @else
            <div class="rounded-xl bg-ink-50 p-4 text-sm leading-6 text-ink-600 ring-1 ring-ink-200">
                <x-heroicon-s-lock-closed class="me-1 inline size-4 text-ink-400" /> {{ __('editor.history.locked') }}
                <a href="{{ route('billing.index') }}" class="font-semibold text-brand-700 underline underline-offset-2">{{ __('billing.see_plans') }}</a>
            </div>
        @endif
    </div>
</div>
