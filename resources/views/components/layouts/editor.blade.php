@props(['title' => null])

<x-layouts.base :title="$title" body-class="h-screen overflow-hidden bg-ink-100 text-ink-800">
    <main id="main" class="h-full">
        {{ $slot }}
    </main>

    {{-- Toasts: Livewire dispatches "notify" with {type, message}. --}}
    <div x-data="{ toasts: [] }"
         @notify.window="const id = Date.now(); toasts.push({ id, ...$event.detail }); setTimeout(() => toasts = toasts.filter(t => t.id !== id), 5000)"
         class="pointer-events-none fixed inset-x-0 bottom-4 z-[60] flex flex-col items-center gap-2 px-4" aria-live="polite">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition
                 :class="{ 'bg-ink-900 text-white': toast.type === 'success', 'bg-amber-50 text-amber-900 ring-amber-200': toast.type === 'warning', 'bg-red-50 text-red-800 ring-red-200': toast.type === 'error', 'bg-white text-ink-800 ring-ink-200': ! ['success', 'warning', 'error'].includes(toast.type) }"
                 class="pointer-events-auto max-w-md rounded-xl px-4 py-3 text-sm shadow-lift ring-1 ring-transparent">
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>
</x-layouts.base>
