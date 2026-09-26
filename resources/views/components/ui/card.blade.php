@props(['padding' => 'p-6'])

<div {{ $attributes->class(['rounded-2xl bg-white shadow-soft ring-1 ring-ink-200/70', $padding]) }}>
    @isset($header)
        <div class="mb-5 flex items-start justify-between gap-4">
            {{ $header }}
        </div>
    @endisset

    {{ $slot }}
</div>
