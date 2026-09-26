<div x-cloak x-show="templates" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="template-picker-title" @keydown.escape.window="templates = false">
    <div x-show="templates" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="templates = false"></div>
    <div x-show="templates" x-transition class="relative flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-lift">
        <div class="flex items-center justify-between border-b border-ink-100 px-6 py-4">
            <div>
                <h2 id="template-picker-title" class="text-base font-semibold text-ink-900">{{ __('editor.change_template') }}</h2>
                <p class="text-sm text-ink-500">{{ __('editor.template_picker_hint') }}</p>
            </div>
            <button type="button" @click="templates = false" class="inline-flex size-9 items-center justify-center rounded-lg text-ink-500 hover:bg-ink-100"><x-heroicon-o-x-mark class="size-5" /><span class="sr-only">{{ __('common.cancel') }}</span></button>
        </div>
        <div class="grid gap-4 overflow-y-auto p-6 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($templates as $template)
                @php($locked = $template->is_premium && ! $canUsePremium)
                <button type="button"
                        @if ($locked) @click="$dispatch('notify', { type: 'warning', message: @js(__('resumes.errors.template_premium')) })" @else wire:click="changeTemplate({{ $template->id }})" @click="templates = false" @endif
                        @class(['group block text-start', 'cursor-not-allowed' => $locked])>
                    <div @class(['overflow-hidden rounded-xl bg-ink-50 p-2 ring-1 transition', 'ring-2 ring-brand-500' => $template->id === $resume->template_id, 'ring-ink-200 group-hover:ring-brand-300' => $template->id !== $resume->template_id])>
                        <div class="relative">
                            <template x-if="templates">
                                <x-resume.thumbnail :src="route('templates.preview', ['template' => $template, 'thumb' => 1]).'&lang='.$resume->language" :label="$template->name" class="rounded shadow-soft" />
                            </template>
                            @if ($locked)
                                <div class="absolute inset-0 flex items-center justify-center bg-white/60"><span class="inline-flex size-9 items-center justify-center rounded-full bg-ink-900 text-white"><x-heroicon-s-lock-closed class="size-4" /></span></div>
                            @endif
                        </div>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-2">
                        <span class="truncate text-sm font-semibold text-ink-900">{{ $template->name }}</span>
                        <x-template.badge :status="$template->ats_status" />
                    </div>
                </button>
            @endforeach
        </div>
    </div>
</div>
