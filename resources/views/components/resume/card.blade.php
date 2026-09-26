@props(['resume'])

<article x-data="{ menu: false, renaming: false, deleting: false }" class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-soft ring-1 ring-ink-200/70 transition hover:shadow-lift">
    <a href="{{ route('resumes.edit', $resume) }}" class="focus-ring relative block border-b border-ink-100 bg-ink-50 p-4" aria-label="{{ __('resumes.actions.edit') }}: {{ $resume->title }}">
        <x-resume.thumbnail :src="route('resumes.preview', ['resume' => $resume, 'thumb' => 1, 'v' => $resume->updated_at?->timestamp])" :label="$resume->title" class="rounded-md shadow-soft ring-1 ring-ink-200/60" />
        <span class="absolute inset-0 flex items-center justify-center bg-ink-900/0 opacity-0 transition group-hover:bg-ink-900/5 group-hover:opacity-100">
            <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-ink-900 shadow-lift">
                <x-heroicon-o-pencil-square class="size-4" aria-hidden="true" /> {{ __('resumes.actions.edit') }}
            </span>
        </span>
    </a>

    <div class="flex flex-1 items-start justify-between gap-3 p-4">
        <div class="min-w-0">
            <h3 class="truncate text-sm font-semibold text-ink-900">{{ $resume->title }}</h3>
            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-ink-500">
                <span>{{ $resume->template?->name }}</span>
                <span aria-hidden="true">·</span>
                <span>{{ strtoupper($resume->language) }}</span>
                <span aria-hidden="true">·</span>
                <time datetime="{{ $resume->updated_at?->toIso8601String() }}">{{ __('resumes.edited', ['time' => $resume->updated_at?->diffForHumans()]) }}</time>
            </p>
        </div>

        <div class="relative" @click.outside="menu = false" @keydown.escape.window="menu = false">
            <button type="button" @click="menu = ! menu" :aria-expanded="menu.toString()" class="focus-ring -me-1 inline-flex size-8 items-center justify-center rounded-lg text-ink-500 hover:bg-ink-100 hover:text-ink-900">
                <x-heroicon-o-ellipsis-vertical class="size-5" aria-hidden="true" />
                <span class="sr-only">{{ __('resumes.actions.menu') }}</span>
            </button>

            <div x-cloak x-show="menu" x-transition.origin.top class="absolute end-0 bottom-full z-30 mb-2 w-52 rounded-xl bg-white p-1.5 shadow-lift ring-1 ring-ink-200">
                <a href="{{ route('resumes.edit', $resume) }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-ink-700 hover:bg-ink-50">
                    <x-heroicon-o-pencil-square class="size-4 text-ink-400" aria-hidden="true" /> {{ __('resumes.actions.edit') }}
                </a>
                <button type="button" @click="renaming = true; menu = false" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-start text-sm text-ink-700 hover:bg-ink-50">
                    <x-heroicon-o-tag class="size-4 text-ink-400" aria-hidden="true" /> {{ __('resumes.actions.rename') }}
                </button>
                <form method="POST" action="{{ route('resumes.duplicate', $resume) }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-start text-sm text-ink-700 hover:bg-ink-50">
                        <x-heroicon-o-document-duplicate class="size-4 text-ink-400" aria-hidden="true" /> {{ __('resumes.actions.duplicate') }}
                    </button>
                </form>
                <div class="my-1 border-t border-ink-100"></div>
                @foreach (['pdf' => 'document-arrow-down', 'docx' => 'document-text'] as $format => $icon)
                    <div x-data="exporter(@js(route('resumes.exports.store', $resume)), @js($format))">
                        <button type="button" @click="run()" :disabled="busy" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-start text-sm text-ink-700 hover:bg-ink-50 disabled:opacity-60">
                            <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-4 text-ink-400" aria-hidden="true" />
                            <span x-show="! busy">{{ __('resumes.actions.download_'.$format) }}</span>
                            <span x-cloak x-show="busy">{{ __('resumes.actions.preparing') }}</span>
                        </button>
                        <p x-cloak x-show="error" x-text="error" class="px-3 pb-2 text-xs text-red-600"></p>
                    </div>
                @endforeach
                <div class="my-1 border-t border-ink-100"></div>
                <button type="button" @click="deleting = true; menu = false" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-start text-sm text-red-600 hover:bg-red-50">
                    <x-heroicon-o-trash class="size-4" aria-hidden="true" /> {{ __('resumes.actions.delete') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Rename --}}
    <div x-cloak x-show="renaming" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" @keydown.escape.window="renaming = false">
        <div class="absolute inset-0 bg-ink-950/50" @click="renaming = false"></div>
        <form method="POST" action="{{ route('resumes.update', $resume) }}" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-lift">
            @csrf
            @method('PATCH')
            <h3 class="text-base font-semibold text-ink-900">{{ __('resumes.rename.title') }}</h3>
            <x-ui.input name="title" id="rename-{{ $resume->uuid }}" :label="__('resumes.fields.title')" :value="$resume->title" class="mt-4" maxlength="150" required x-effect="renaming && $nextTick(() => $el.select())" />
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button variant="secondary" x-on:click="renaming = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('common.save') }}</x-ui.button>
            </div>
        </form>
    </div>

    {{-- Delete --}}
    <div x-cloak x-show="deleting" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" @keydown.escape.window="deleting = false">
        <div class="absolute inset-0 bg-ink-950/50" @click="deleting = false"></div>
        <form method="POST" action="{{ route('resumes.destroy', $resume) }}" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-lift">
            @csrf
            @method('DELETE')
            <div class="flex items-start gap-4">
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                    <x-heroicon-o-trash class="size-5" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="text-base font-semibold text-ink-900">{{ __('resumes.delete.title') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-ink-500">{{ __('resumes.delete.body', ['title' => $resume->title]) }}</p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button variant="secondary" x-on:click="deleting = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" variant="danger">{{ __('resumes.actions.delete') }}</x-ui.button>
            </div>
        </form>
    </div>
</article>
