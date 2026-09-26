@props(['type' => 'success'])

@php
    $styles = [
        'success' => ['bg-emerald-50 text-emerald-800 ring-emerald-200', 'check-circle'],
        'info' => ['bg-brand-50 text-brand-800 ring-brand-200', 'information-circle'],
        'warning' => ['bg-amber-50 text-amber-800 ring-amber-200', 'exclamation-triangle'],
        'error' => ['bg-red-50 text-red-800 ring-red-200', 'x-circle'],
    ];
    [$classes, $icon] = $styles[$type] ?? $styles['info'];
@endphp

<div role="{{ $type === 'error' ? 'alert' : 'status' }}" {{ $attributes->class(['flex items-start gap-3 rounded-xl px-4 py-3 text-sm ring-1 ring-inset', $classes]) }}>
    <x-dynamic-component :component="'heroicon-o-'.$icon" class="mt-px size-5 shrink-0" aria-hidden="true" />
    <div class="min-w-0 flex-1">{{ $slot }}</div>
</div>
