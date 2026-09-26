{{-- Real template render scaled down to the card width. Sandboxed: no scripts, same-origin fonts only. --}}
@props(['src', 'label' => ''])

<div x-data="resumeThumbnail" {{ $attributes->class('relative aspect-[210/297] w-full overflow-hidden bg-white') }}>
    <iframe
        src="{{ $src }}"
        loading="lazy"
        tabindex="-1"
        aria-hidden="true"
        title="{{ $label }}"
        sandbox="allow-same-origin"
        class="pointer-events-none absolute top-0 left-0 origin-top-left border-0"
        style="width: 794px; height: 1123px; transform: scale(0.3)"
        :style="`width: 794px; height: 1123px; transform: scale(${scale})`"
    ></iframe>
</div>
