@props(['label', 'value', 'hint' => null, 'icon' => 'chart-bar'])

<div class="rounded-2xl bg-white p-5 shadow-soft ring-1 ring-ink-200/70">
    <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-ink-500">{{ $label }}</p>
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5 text-ink-300" aria-hidden="true" />
    </div>
    <p class="mt-2 text-3xl font-semibold tracking-tight text-ink-900 tabular-nums" dir="ltr">{{ $value }}</p>
    @if ($hint) <p class="mt-1 text-xs text-ink-500">{{ $hint }}</p> @endif
</div>
