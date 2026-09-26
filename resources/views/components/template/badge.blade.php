@props(['status'])

@php($status = $status instanceof \App\Enums\AtsStatus ? $status : \App\Enums\AtsStatus::from($status))

<x-ui.badge :color="$status->isAtsFriendly() ? 'emerald' : 'violet'" {{ $attributes }}>
    @if ($status->isAtsFriendly())
        <x-heroicon-s-shield-check class="size-3" aria-hidden="true" />
    @else
        <x-heroicon-s-paint-brush class="size-3" aria-hidden="true" />
    @endif
    {{ $status->label() }}
</x-ui.badge>
