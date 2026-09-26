@props([
    'name',
    'id' => null,
    'bag' => 'default',
    'checked' => false,
])

@php
    $id ??= $name;
    $error = $errors->getBag($bag)->first($name);
@endphp

<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="flex cursor-pointer items-start gap-3 text-sm text-ink-600">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="checkbox"
            value="1"
            @checked(old($name, $checked))
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->except('class')->class('mt-0.5 size-4 shrink-0 rounded border-ink-300 text-brand-600 focus:ring-brand-500') }}
        >
        <span>{{ $slot }}</span>
    </label>

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 text-xs font-medium text-red-600">{{ $error }}</p>
    @endif
</div>
