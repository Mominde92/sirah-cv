@php
    $aiAvailable = $this->aiAvailable();
    $aiAllowed = $aiAvailable && $this->aiAllowed();
    $hasSidebar = in_array($styling['layout'] ?? 'single-column', ['sidebar-left', 'sidebar-right'], true);
@endphp

<div class="space-y-3 p-4">
    {{-- Personal information --}}
    <div x-data="{ open: @js($openSection === 'personal') }" class="rounded-2xl ring-1 ring-ink-200">
        <button type="button" @click="open = ! open" :aria-expanded="open.toString()" class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-start">
            <span class="inline-flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><x-heroicon-o-identification class="size-4" aria-hidden="true" /></span>
            <span class="flex-1">
                <span class="block text-sm font-semibold text-ink-900">{{ __('editor.personal.title') }}</span>
                <span class="block truncate text-xs text-ink-500">{{ trim(($personal['first_name'] ?? '').' '.($personal['last_name'] ?? '')) ?: __('editor.personal.subtitle') }}</span>
            </span>
            <x-heroicon-o-chevron-down class="size-4 text-ink-400 transition" x-bind:class="open && 'rotate-180'" aria-hidden="true" />
        </button>

        <div x-show="open" x-collapse class="border-t border-ink-100 p-4">
            <div class="mb-4 flex items-center gap-4">
                <div class="relative size-16 shrink-0 overflow-hidden rounded-full bg-ink-100 ring-1 ring-ink-200">
                    @if (($personal['photo_path'] ?? '') !== '')
                        <img src="{{ route('resumes.photo', ['resume' => $resume, 'v' => md5($personal['photo_path'])]) }}" alt="{{ __('editor.personal.photo_alt') }}" class="size-full object-cover">
                    @else
                        <x-heroicon-s-user class="absolute inset-3 text-ink-300" aria-hidden="true" />
                    @endif
                    <div wire:loading wire:target="photo" class="absolute inset-0 flex items-center justify-center bg-white/80"><x-heroicon-o-arrow-path class="size-5 animate-spin text-brand-600" /></div>
                </div>
                <div class="space-y-1">
                    <div class="flex flex-wrap gap-2">
                        <label class="focus-ring inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-lg px-3 text-xs font-medium text-ink-700 ring-1 ring-ink-200 hover:bg-ink-50">
                            <x-heroicon-o-camera class="size-4" aria-hidden="true" /> {{ ($personal['photo_path'] ?? '') !== '' ? __('editor.personal.replace_photo') : __('editor.personal.add_photo') }}
                            <input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" class="sr-only">
                        </label>
                        @if (($personal['photo_path'] ?? '') !== '')
                            <button type="button" wire:click="removePhoto" class="h-8 rounded-lg px-3 text-xs font-medium text-red-600 hover:bg-red-50">{{ __('editor.personal.remove_photo') }}</button>
                        @endif
                    </div>
                    <p class="text-xs text-ink-500">{{ $photoAllowed ? __('editor.personal.photo_hint') : __('editor.personal.photo_ats') }}</p>
                    @error('photo') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                @foreach ($personalFields as $name => $field)
                    <div @class(['col-span-2' => in_array($name, ['headline', 'email', 'website', 'linkedin', 'github'], true)])>
                        <label for="personal-{{ $name }}" class="mb-1 block text-xs font-medium text-ink-600">{{ __('editor.fields.'.$name) }}</label>
                        <input id="personal-{{ $name }}" type="{{ $field['type'] === 'email' ? 'email' : ($field['type'] === 'url' ? 'url' : 'text') }}"
                               wire:model.live.debounce.700ms="personal.{{ $name }}" maxlength="{{ $field['max'] }}"
                               @if (in_array($field['type'], ['email', 'url'], true) || $name === 'phone') dir="ltr" @else dir="auto" @endif
                               placeholder="{{ __('editor.placeholders.'.$name) }}"
                               class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-ink-900 ring-1 ring-inset ring-ink-200 placeholder:text-ink-300 focus:ring-2 focus:ring-brand-500">
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Sections (drag to reorder) --}}
    <ul x-sort="$wire.sortSections($item, $position)" class="space-y-3">
        @foreach ($sections as $si => $section)
            @php
                $type = \App\Enums\SectionType::from($section['type']);
                $fields = $schema->fields($type);
                $labelField = $schema->labelField($type);
                $displayTitle = $section['title'] !== '' ? $section['title'] : $type->defaultTitle($resume->language);
            @endphp
            <li wire:key="section-{{ $section['key'] }}" x-sort:item="@js($section['key'])"
                x-data="{ open: @js($openSection === $section['key']), menu: false }"
                @class(['rounded-2xl bg-white ring-1 ring-ink-200', 'opacity-60' => ! $section['is_visible']])>
                <div class="flex items-center gap-2 px-2 py-2">
                    <span x-sort:handle class="inline-flex size-8 cursor-grab items-center justify-center rounded-lg text-ink-300 hover:bg-ink-50 hover:text-ink-500 active:cursor-grabbing" title="{{ __('editor.drag') }}">
                        <x-heroicon-o-bars-2 class="size-4" aria-hidden="true" />
                    </span>
                    <button type="button" @click="open = ! open" :aria-expanded="open.toString()" class="min-w-0 flex-1 py-1 text-start">
                        <span class="block truncate text-sm font-semibold text-ink-900">{{ $displayTitle }}</span>
                        <span class="block text-xs text-ink-500">
                            {{ $type->isSingleEntry() ? __('editor.single_entry') : trans_choice('editor.entries', count($section['items']), ['count' => count($section['items'])]) }}
                            @unless ($section['is_visible']) · {{ __('editor.hidden') }} @endunless
                        </span>
                    </button>
                    <button type="button" wire:click="toggleSection(@js($section['key']))" class="inline-flex size-8 items-center justify-center rounded-lg text-ink-400 hover:bg-ink-50 hover:text-ink-700" title="{{ $section['is_visible'] ? __('editor.hide') : __('editor.show') }}">
                        @if ($section['is_visible'])
                            <x-heroicon-o-eye class="size-4" aria-hidden="true" />
                        @else
                            <x-heroicon-o-eye-slash class="size-4" aria-hidden="true" />
                        @endif
                    </button>
                    <div class="relative" @click.outside="menu = false">
                        <button type="button" @click="menu = ! menu" class="inline-flex size-8 items-center justify-center rounded-lg text-ink-400 hover:bg-ink-50 hover:text-ink-700" title="{{ __('resumes.actions.menu') }}">
                            <x-heroicon-o-ellipsis-horizontal class="size-4" aria-hidden="true" />
                        </button>
                        <div x-cloak x-show="menu" x-transition.origin.top class="absolute end-0 top-full z-30 mt-1 w-48 rounded-xl bg-white p-1 shadow-lift ring-1 ring-ink-200">
                            <button type="button" wire:click="moveSection(@js($section['key']), -1)" @click="menu = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-start text-sm text-ink-700 hover:bg-ink-50"><x-heroicon-o-arrow-up class="size-4 text-ink-400" /> {{ __('editor.move_up') }}</button>
                            <button type="button" wire:click="moveSection(@js($section['key']), 1)" @click="menu = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-start text-sm text-ink-700 hover:bg-ink-50"><x-heroicon-o-arrow-down class="size-4 text-ink-400" /> {{ __('editor.move_down') }}</button>
                            <button type="button" wire:click="removeSection(@js($section['key']))" wire:confirm="{{ __('editor.confirm_remove_section') }}" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-start text-sm text-red-600 hover:bg-red-50"><x-heroicon-o-trash class="size-4" /> {{ __('editor.remove_section') }}</button>
                        </div>
                    </div>
                    <button type="button" @click="open = ! open" class="inline-flex size-8 items-center justify-center rounded-lg text-ink-400 hover:bg-ink-50" tabindex="-1" aria-hidden="true">
                        <x-heroicon-o-chevron-down class="size-4 transition" x-bind:class="open && 'rotate-180'" />
                    </button>
                </div>

                <div x-show="open" x-collapse class="space-y-3 border-t border-ink-100 p-3">
                    <div class="grid gap-2 {{ $hasSidebar ? 'grid-cols-3' : 'grid-cols-1' }}">
                        <div class="{{ $hasSidebar ? 'col-span-2' : '' }}">
                            <label for="section-title-{{ $section['key'] }}" class="mb-1 block text-xs font-medium text-ink-600">{{ __('editor.section_title') }}</label>
                            <input id="section-title-{{ $section['key'] }}" type="text" wire:model.live.debounce.800ms="sections.{{ $si }}.title" maxlength="120" dir="auto" placeholder="{{ $type->defaultTitle($resume->language) }}"
                                   class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-ink-200 placeholder:text-ink-400 focus:ring-2 focus:ring-brand-500">
                        </div>
                        @if ($hasSidebar)
                            <div>
                                <label for="section-column-{{ $section['key'] }}" class="mb-1 block text-xs font-medium text-ink-600">{{ __('editor.column') }}</label>
                                <select id="section-column-{{ $section['key'] }}" wire:model.live="sections.{{ $si }}.column" class="block w-full rounded-lg border-0 py-2 ps-3 pe-8 text-sm ring-1 ring-inset ring-ink-200 focus:ring-2 focus:ring-brand-500">
                                    <option value="">{{ __('editor.column_auto') }}</option>
                                    <option value="main">{{ __('editor.column_main') }}</option>
                                    <option value="sidebar">{{ __('editor.column_sidebar') }}</option>
                                </select>
                            </div>
                        @endif
                    </div>

                    @if ($type === \App\Enums\SectionType::Skills && $aiAvailable)
                        @include('livewire.editor.partials.ai-button', ['action' => "aiSkills('{$section['key']}')", 'label' => __('ai.suggest_skills'), 'target' => 'aiSkills', 'allowed' => $aiAllowed])
                    @endif

                    <ul x-sort="$wire.sortItems(@js($section['key']), $item, $position)" class="space-y-2">
                        @foreach ($section['items'] as $ii => $item)
                            @php($label = is_string($item[$labelField] ?? null) && $item[$labelField] !== '' ? \Illuminate\Support\Str::limit($item[$labelField], 60) : __('editor.new_entry'))
                            <li wire:key="item-{{ $item['id'] }}" x-sort:item="@js($item['id'])"
                                x-data="{ open: @js($openItem === $item['id'] || $type->isSingleEntry() || count($section['items']) === 1) }"
                                class="rounded-xl bg-ink-50/70 ring-1 ring-ink-200/70">
                                @unless ($type->isSingleEntry())
                                    <div class="flex items-center gap-1 px-1.5 py-1.5">
                                        <span x-sort:handle class="inline-flex size-7 cursor-grab items-center justify-center rounded-md text-ink-300 hover:text-ink-500"><x-heroicon-o-bars-2 class="size-4" aria-hidden="true" /></span>
                                        <button type="button" @click="open = ! open" class="min-w-0 flex-1 truncate py-1 text-start text-sm font-medium text-ink-800">{{ $label }}</button>
                                        <button type="button" wire:click="duplicateItem(@js($section['key']), @js($item['id']))" class="inline-flex size-7 items-center justify-center rounded-md text-ink-400 hover:bg-white hover:text-ink-700" title="{{ __('editor.duplicate_entry') }}"><x-heroicon-o-document-duplicate class="size-4" aria-hidden="true" /></button>
                                        <button type="button" wire:click="removeItem(@js($section['key']), @js($item['id']))" wire:confirm="{{ __('editor.confirm_remove_entry') }}" class="inline-flex size-7 items-center justify-center rounded-md text-ink-400 hover:bg-white hover:text-red-600" title="{{ __('editor.remove_entry') }}"><x-heroicon-o-trash class="size-4" aria-hidden="true" /></button>
                                    </div>
                                @endunless

                                <div x-show="open" x-collapse @class(['grid grid-cols-2 gap-3 p-3', 'border-t border-ink-200/70' => ! $type->isSingleEntry()])>
                                    @foreach ($fields as $name => $field)
                                        @include('livewire.editor.partials.field', [
                                            'model' => "sections.{$si}.items.{$ii}.{$name}",
                                            'id' => "f-{$item['id']}-{$name}",
                                            'name' => $name,
                                            'field' => $field,
                                            'item' => $item,
                                            'type' => $type,
                                            'sectionKey' => $section['key'],
                                            'aiAvailable' => $aiAvailable,
                                            'aiAllowed' => $aiAllowed,
                                        ])
                                    @endforeach
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    @unless ($type->isSingleEntry() && count($section['items']) >= 1)
                        <button type="button" wire:click="addItem(@js($section['key']))" class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-dashed border-ink-300 py-2 text-sm font-medium text-ink-600 hover:border-brand-400 hover:bg-brand-50/50 hover:text-brand-700">
                            <x-heroicon-o-plus class="size-4" aria-hidden="true" /> {{ __('editor.add_entry') }}
                        </button>
                    @endunless
                </div>
            </li>
        @endforeach
    </ul>

    {{-- Add a section --}}
    <div x-data="{ open: false }" class="relative" @click.outside="open = false">
        <button type="button" @click="open = ! open" class="flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-ink-200 py-3 text-sm font-semibold text-ink-600 hover:border-brand-300 hover:text-brand-700">
            <x-heroicon-o-plus-circle class="size-5" aria-hidden="true" /> {{ __('editor.add_section') }}
        </button>
        <div x-cloak x-show="open" x-transition.origin.bottom class="absolute inset-x-0 bottom-full z-30 mb-2 grid max-h-80 grid-cols-2 gap-1 overflow-y-auto rounded-2xl bg-white p-2 shadow-lift ring-1 ring-ink-200">
            @foreach ($availableSections as $available)
                <button type="button" wire:click="addSection(@js($available->value))" @click="open = false" class="rounded-lg px-3 py-2 text-start text-sm text-ink-700 hover:bg-brand-50 hover:text-brand-700">
                    {{ $available->defaultTitle() }}
                </button>
            @endforeach
        </div>
    </div>
</div>
