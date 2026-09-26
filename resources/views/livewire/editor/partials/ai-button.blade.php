{{-- Expects: $action (Livewire call), $label, $target (method name for loading state), $allowed; optional $compact, $icon. --}}
@php($icon ??= 'sparkles')
@php($compact ??= false)

@if ($allowed)
    <button type="button" wire:click="{{ $action }}" wire:loading.attr="disabled" wire:target="{{ $target }}"
            @class([
                'inline-flex items-center gap-1 rounded-md font-medium text-violet-700 hover:bg-violet-50 disabled:opacity-60',
                'px-1.5 py-0.5 text-xs' => $compact,
                'w-full justify-center rounded-xl py-2 text-sm ring-1 ring-violet-200 bg-violet-50/50' => ! $compact,
            ])>
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-3.5" wire:loading.remove wire:target="{{ $target }}" aria-hidden="true" />
        <x-heroicon-o-arrow-path class="size-3.5 animate-spin" wire:loading wire:target="{{ $target }}" aria-hidden="true" />
        {{ $label }}
    </button>
@else
    <button type="button" @click="$dispatch('notify', { type: 'warning', message: @js(__('ai.upgrade')) })"
            @class([
                'inline-flex items-center gap-1 rounded-md font-medium text-ink-400 hover:bg-ink-50',
                'px-1.5 py-0.5 text-xs' => $compact,
                'w-full justify-center rounded-xl py-2 text-sm ring-1 ring-ink-200' => ! $compact,
            ])>
        <x-heroicon-o-lock-closed class="size-3.5" aria-hidden="true" /> {{ $label }}
    </button>
@endif
