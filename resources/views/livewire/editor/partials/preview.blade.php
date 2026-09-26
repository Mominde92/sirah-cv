{{-- wire:ignore keeps the iframe alive across Livewire re-renders; new HTML arrives via the preview-updated event. --}}
<div wire:ignore x-data="previewFrame(@js($initialPreview))" @preview-updated.window="html = $event.detail.html" class="flex h-full min-h-0 flex-col">
    <div class="flex h-11 shrink-0 items-center justify-between gap-3 border-b border-ink-200 bg-white/70 px-4 text-xs text-ink-500 backdrop-blur">
        <span class="inline-flex items-center gap-1.5"><x-heroicon-o-document class="size-4" aria-hidden="true" /> A4 · {{ __('editor.live_preview') }}</span>
        <label class="inline-flex items-center gap-2">
            <span class="sr-only">{{ __('editor.zoom') }}</span>
            <x-heroicon-o-magnifying-glass class="size-4" aria-hidden="true" />
            <select x-model="zoom" class="rounded-md border-0 bg-transparent py-1 ps-2 pe-7 text-xs ring-1 ring-ink-200 focus:ring-2 focus:ring-brand-500">
                <option value="fit">{{ __('editor.zoom_fit') }}</option>
                <option value="0.75">75%</option>
                <option value="1">100%</option>
                <option value="1.25">125%</option>
            </select>
        </label>
    </div>

    <div x-ref="viewport" class="min-h-0 flex-1 overflow-auto px-3 py-2">
        <div class="relative mx-auto" :style="`width: ${docWidth * scale}px; height: ${height * scale}px`">
            <iframe x-ref="frame" title="{{ __('editor.live_preview') }}" tabindex="-1"
                    class="absolute top-0 left-0 origin-top-left border-0"
                    :style="`width: ${docWidth}px; height: ${height}px; transform: scale(${scale})`"></iframe>
        </div>
    </div>
</div>
