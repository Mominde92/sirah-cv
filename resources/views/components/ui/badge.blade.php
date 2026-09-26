@props(['color' => 'ink'])

@php
    $colors = [
        'ink' => 'bg-ink-100 text-ink-700 ring-ink-200',
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-200',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset', $colors[$color] ?? $colors['ink']]) }}>
    {{ $slot }}
</span>
