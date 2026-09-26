<div class="flex h-full flex-col"
     x-data="{ panel: 'content', lg: window.innerWidth >= 1024, wide: window.innerWidth >= 1280, templates: false }"
     @resize.window.debounce.150ms="lg = window.innerWidth >= 1024; wide = window.innerWidth >= 1280">

    {{-- Top bar --}}
    <header class="flex h-14 shrink-0 items-center gap-2 border-b border-ink-200 bg-white px-2 sm:gap-3 sm:px-4">
        <a href="{{ route('resumes.index') }}" class="focus-ring inline-flex size-9 shrink-0 items-center justify-center rounded-lg text-ink-500 hover:bg-ink-100 hover:text-ink-900" title="{{ __('editor.back') }}">
            <x-heroicon-o-arrow-left class="size-5 flip-rtl" aria-hidden="true" />
            <span class="sr-only">{{ __('editor.back') }}</span>
        </a>

        <div class="min-w-0 flex-1">
            <label for="resume-title" class="sr-only">{{ __('resumes.fields.title') }}</label>
            <input id="resume-title" type="text" wire:model.live.debounce.800ms="title" maxlength="150"
                   class="block w-full max-w-xs truncate rounded-lg border-0 bg-transparent px-2 py-1 text-sm font-semibold text-ink-900 hover:bg-ink-50 focus:bg-white focus:ring-2 focus:ring-brand-500">
            <p class="flex items-center gap-1.5 px-2 text-xs text-ink-500">
                <span wire:loading.delay.shortest class="inline-flex items-center gap-1"><x-heroicon-o-arrow-path class="size-3 animate-spin" aria-hidden="true" /> {{ __('editor.saving') }}</span>
                <span wire:loading.remove>
                    @if ($saveState === 'conflict')
                        <span class="text-amber-600">{{ __('editor.conflict_short') }}</span>
                    @else
                        <span class="inline-flex items-center gap-1"><x-heroicon-s-check-circle class="size-3 text-emerald-500" aria-hidden="true" /> {{ __('editor.saved') }}</span>
                    @endif
                </span>
                <span aria-hidden="true">·</span>
                <span>{{ $resume->template->name }}</span>
            </p>
        </div>

        <button type="button" @click="templates = true" class="focus-ring hidden h-9 items-center gap-2 rounded-lg px-3 text-sm font-medium text-ink-700 ring-1 ring-ink-200 hover:bg-ink-50 sm:inline-flex">
            <x-heroicon-o-swatch class="size-4" aria-hidden="true" /> {{ __('editor.change_template') }}
        </button>

        <a href="{{ route('resumes.optimize', $resume) }}" class="focus-ring hidden h-9 items-center gap-2 rounded-lg px-3 text-sm font-medium text-ink-700 ring-1 ring-ink-200 hover:bg-ink-50 md:inline-flex">
            <x-heroicon-o-briefcase class="size-4" aria-hidden="true" /> {{ __('editor.optimize') }}
        </a>

        <button type="button" x-show="lg && ! wide" @click="panel = panel === 'design' ? 'content' : 'design'" class="focus-ring inline-flex h-9 items-center gap-2 rounded-lg px-3 text-sm font-medium text-ink-700 ring-1 ring-ink-200 hover:bg-ink-50">
            <x-heroicon-o-adjustments-horizontal class="size-4" aria-hidden="true" />
            <span x-text="panel === 'design' ? @js(__('editor.panels.content')) : @js(__('editor.panels.design'))"></span>
        </button>

        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
            <button type="button" @click="open = ! open" class="focus-ring inline-flex h-9 items-center gap-2 rounded-lg bg-brand-600 px-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                <x-heroicon-o-arrow-down-tray class="size-4" aria-hidden="true" />
                <span class="hidden sm:inline">{{ __('editor.download') }}</span>
            </button>
            <div x-cloak x-show="open" x-transition.origin.top class="absolute end-0 top-full z-40 mt-2 w-60 rounded-xl bg-white p-1.5 shadow-lift ring-1 ring-ink-200">
                @foreach (['pdf' => 'document-arrow-down', 'docx' => 'document-text'] as $format => $icon)
                    <div x-data="exporter(@js(route('resumes.exports.store', $resume)), @js($format))">
                        <button type="button" @click="run()" :disabled="busy" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-start text-sm hover:bg-ink-50 disabled:opacity-60">
                            <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5 text-ink-400" aria-hidden="true" />
                            <span class="flex-1">
                                <span class="block font-medium text-ink-900" x-text="busy ? @js(__('resumes.actions.preparing')) : @js(__('resumes.actions.download_'.$format))"></span>
                                <span class="block text-xs text-ink-500">{{ __('editor.format_hint.'.$format) }}</span>
                            </span>
                        </button>
                        <p x-cloak x-show="error" x-text="error" class="px-3 pb-2 text-xs text-red-600"></p>
                    </div>
                @endforeach
            </div>
        </div>
    </header>

    @if ($saveState === 'conflict')
        <div class="flex items-center justify-between gap-3 border-b border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-900" role="alert">
            <span>{{ __('editor.conflict') }}</span>
            <button type="button" wire:click="reloadFromServer" class="font-semibold underline underline-offset-2">{{ __('editor.reload') }}</button>
        </div>
    @endif

    {{-- Mobile panel switcher --}}
    <nav x-show="! lg" class="flex shrink-0 border-b border-ink-200 bg-white" aria-label="{{ __('editor.panels.label') }}">
        @foreach (['content' => 'pencil-square', 'preview' => 'eye', 'design' => 'adjustments-horizontal'] as $key => $icon)
            <button type="button" @click="panel = @js($key)" :class="panel === @js($key) ? 'border-brand-600 text-brand-700' : 'border-transparent text-ink-500'"
                    class="flex flex-1 items-center justify-center gap-1.5 border-b-2 py-2.5 text-sm font-medium">
                <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-4" aria-hidden="true" /> {{ __('editor.panels.'.$key) }}
            </button>
        @endforeach
    </nav>

    <div class="flex min-h-0 flex-1">
        <aside x-show="lg ? (wide || panel !== 'design') : panel === 'content'"
               class="flex w-full shrink-0 flex-col overflow-y-auto border-e border-ink-200 bg-white lg:w-[400px] xl:w-[420px]"
               aria-label="{{ __('editor.panels.content') }}">
            @include('livewire.editor.partials.content-panel')
        </aside>

        <section x-show="lg || panel === 'preview'" class="flex min-w-0 flex-1 flex-col" aria-label="{{ __('editor.panels.preview') }}">
            @include('livewire.editor.partials.preview')
        </section>

        <aside x-show="wide || panel === 'design'"
               class="flex w-full shrink-0 flex-col overflow-y-auto border-s border-ink-200 bg-white lg:w-[400px] xl:w-[320px]"
               aria-label="{{ __('editor.panels.design') }}">
            @include('livewire.editor.partials.design-panel')
        </aside>
    </div>

    @include('livewire.editor.partials.template-picker')
    @include('livewire.editor.partials.ai-suggestion')
</div>
