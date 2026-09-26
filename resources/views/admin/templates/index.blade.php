<x-admin.shell :title="__('admin.nav.templates')">
    <div class="mb-4 flex justify-end">
        <x-ui.button :href="route('admin.templates.create')" icon="plus">{{ __('admin.templates.new') }}</x-ui.button>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($templates as $template)
            <article @class(['overflow-hidden rounded-2xl bg-white shadow-soft ring-1 ring-ink-200/70', 'opacity-60' => ! $template->is_active])>
                <div class="bg-ink-50 p-3">
                    @php($base = route('templates.preview', ['template' => $template, 'thumb' => 1]))
                    @if ($template->is_active)
                        <x-resume.thumbnail :src="$base.'&lang=en'" :label="$template->name" class="rounded shadow-soft" />
                    @else
                        <div class="flex aspect-[210/297] items-center justify-center rounded bg-white text-sm text-ink-400">{{ __('admin.templates.inactive') }}</div>
                    @endif
                </div>
                <div class="space-y-2 p-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="truncate text-sm font-semibold text-ink-900">{{ $template->name }}</h3>
                        <x-template.badge :status="$template->ats_status" />
                    </div>
                    <p class="text-xs text-ink-500">{{ $template->slug }} · {{ __('editor.design.layouts.'.$template->layout) }} · {{ trans_choice('admin.templates.used_by', $template->resumes_count, ['count' => $template->resumes_count]) }}</p>
                    <div class="flex flex-wrap gap-1">
                        @if ($template->is_premium) <x-ui.badge color="violet">{{ __('templates.premium') }}</x-ui.badge> @else <x-ui.badge>{{ __('billing.free') }}</x-ui.badge> @endif
                        <x-ui.badge :color="$template->is_active ? 'emerald' : 'amber'">{{ $template->is_active ? __('admin.templates.active') : __('admin.templates.inactive') }}</x-ui.badge>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <x-ui.button :href="route('admin.templates.edit', $template)" size="sm" variant="secondary" class="flex-1">{{ __('admin.edit') }}</x-ui.button>
                        <form method="POST" action="{{ route('admin.templates.toggle', $template) }}" class="flex-1">@csrf <x-ui.button type="submit" size="sm" variant="ghost" class="w-full">{{ $template->is_active ? __('admin.templates.deactivate') : __('admin.templates.activate') }}</x-ui.button></form>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</x-admin.shell>
