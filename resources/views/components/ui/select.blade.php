@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'bag' => 'default',
    'id' => null,
])

@php
    $id ??= $name;
    $error = $errors->getBag($bag)->first($name);
    $current = (string) old($name, $selected);
@endphp

<div {{ $attributes->only('class')->class(['space-y-1.5']) }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-ink-700">{{ $label }}</label>
    @endif

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except('class')->class([
            'block w-full rounded-xl border-0 bg-white py-2.5 ps-3.5 pe-10 text-sm text-ink-900 shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-inset focus:outline-none',
            'ring-ink-200 focus:ring-brand-500' => ! $error,
            'ring-red-300 focus:ring-red-500' => $error,
        ]) }}
    >
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected($current === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>

    @if ($error)
        <p id="{{ $id }}-error" class="text-xs font-medium text-red-600">{{ $error }}</p>
    @endif
</div>
