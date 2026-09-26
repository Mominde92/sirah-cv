@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'iconTrailing' => null,
])

@php
    $base = 'focus-ring inline-flex items-center justify-center gap-2 rounded-xl font-medium whitespace-nowrap transition duration-150 disabled:pointer-events-none disabled:opacity-50';

    $variants = [
        'primary' => 'bg-brand-600 text-white shadow-sm shadow-brand-600/20 hover:bg-brand-700 active:bg-brand-800',
        'secondary' => 'bg-white text-ink-800 ring-1 ring-inset ring-ink-200 shadow-sm hover:bg-ink-50 hover:ring-ink-300',
        'ghost' => 'text-ink-600 hover:bg-ink-100 hover:text-ink-900',
        'danger' => 'bg-red-600 text-white shadow-sm hover:bg-red-700',
        'dark' => 'bg-ink-900 text-white hover:bg-ink-800',
        'light' => 'bg-white text-brand-700 hover:bg-brand-50',
    ];

    $sizes = [
        'sm' => 'h-9 px-3.5 text-sm',
        'md' => 'h-11 px-5 text-sm',
        'lg' => 'h-12 px-6 text-base',
    ];

    $iconSize = $size === 'lg' ? 'size-5' : 'size-4';
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon) <x-dynamic-component :component="'heroicon-o-'.$icon" class="{{ $iconSize }}" aria-hidden="true" /> @endif
        {{ $slot }}
        @if ($iconTrailing) <x-dynamic-component :component="'heroicon-o-'.$iconTrailing" class="{{ $iconSize }} flip-rtl" aria-hidden="true" /> @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon) <x-dynamic-component :component="'heroicon-o-'.$icon" class="{{ $iconSize }}" aria-hidden="true" /> @endif
        {{ $slot }}
        @if ($iconTrailing) <x-dynamic-component :component="'heroicon-o-'.$iconTrailing" class="{{ $iconSize }} flip-rtl" aria-hidden="true" /> @endif
    </button>
@endif
