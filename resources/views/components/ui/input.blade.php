@props([
    'name',
    'label' => null,
    'type' => 'text',
    'hint' => null,
    'bag' => 'default',
    'id' => null,
    'value' => null,
])

@php
    $id ??= $name;
    $error = $errors->getBag($bag)->first($name);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
@endphp

<div {{ $attributes->only('class')->class(['space-y-1.5']) }}>
    @if ($label || isset($labelAside))
        <div class="flex items-center justify-between gap-3">
            @if ($label)
                <label for="{{ $id }}" class="block text-sm font-medium text-ink-700">{{ $label }}</label>
            @endif
            {{ $labelAside ?? '' }}
        </div>
    @endif

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if (! in_array($type, ['password', 'file'], true)) value="{{ old($name, $value) }}" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($error) aria-invalid="true" @endif
        {{ $attributes->except('class')->class([
            'block w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm text-ink-900 shadow-sm ring-1 ring-inset transition placeholder:text-ink-400 focus:ring-2 focus:ring-inset focus:outline-none',
            'ring-ink-200 focus:ring-brand-500' => ! $error,
            'ring-red-300 focus:ring-red-500' => $error,
            'file:me-3 file:rounded-lg file:border-0 file:bg-ink-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-ink-700 hover:file:bg-ink-200 py-2' => $type === 'file',
        ]) }}
    >

    @if ($hint && ! $error)
        <p id="{{ $id }}-hint" class="text-xs text-ink-500">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="text-xs font-medium text-red-600">{{ $error }}</p>
    @endif
</div>
