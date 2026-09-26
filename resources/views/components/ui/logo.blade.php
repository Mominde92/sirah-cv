@props(['href' => route('home'), 'inverted' => false])

<a href="{{ $href }}" {{ $attributes->class(['focus-ring inline-flex items-center gap-2.5 rounded-lg']) }} aria-label="{{ __('common.brand') }}">
    <svg class="size-8 shrink-0" viewBox="0 0 32 32" fill="none" aria-hidden="true">
        <rect width="32" height="32" rx="9" class="{{ $inverted ? 'fill-white' : 'fill-brand-600' }}"/>
        <path d="M10 9.5h8.5L22 13v9.5a1 1 0 0 1-1 1H10a1 1 0 0 1-1-1v-12a1 1 0 0 1 1-1Z" class="{{ $inverted ? 'fill-brand-600' : 'fill-white' }}" fill-opacity=".95"/>
        <path d="M12 15.5h7M12 18.5h7M12 21h4" stroke-width="1.6" stroke-linecap="round" class="{{ $inverted ? 'stroke-white' : 'stroke-brand-600' }}"/>
        <circle cx="13.2" cy="12.4" r="1.4" class="{{ $inverted ? 'fill-white' : 'fill-brand-600' }}"/>
    </svg>
    <span class="text-lg font-semibold tracking-tight {{ $inverted ? 'text-white' : 'text-ink-900' }}">{{ __('common.brand') }}</span>
</a>
