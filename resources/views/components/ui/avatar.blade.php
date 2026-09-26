@props(['user', 'url' => null, 'size' => 'size-9'])

@if ($url)
    <img src="{{ $url }}" alt="" {{ $attributes->class([$size, 'shrink-0 rounded-full object-cover ring-2 ring-white']) }}>
@else
    <span {{ $attributes->class([$size, 'inline-flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-semibold text-white ring-2 ring-white']) }} aria-hidden="true">
        {{ $user->initials() }}
    </span>
@endif
