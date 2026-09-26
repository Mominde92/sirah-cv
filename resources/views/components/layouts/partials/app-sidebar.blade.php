<div class="flex h-16 shrink-0 items-center px-6">
    <x-ui.logo />
</div>

<nav class="flex flex-1 flex-col gap-1 overflow-y-auto px-3 py-4" aria-label="{{ __('common.main_navigation') }}">
    @foreach ($navigation as $item)
        @php($isActive = request()->routeIs($item['active']))
        <a href="{{ route($item['route']) }}"
           @if ($isActive) aria-current="page" @endif
           @class([
               'focus-ring group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
               'bg-brand-50 text-brand-700' => $isActive,
               'text-ink-600 hover:bg-ink-50 hover:text-ink-900' => ! $isActive,
           ])>
            <x-dynamic-component :component="'heroicon-o-'.$item['icon']" :class="$isActive ? 'size-5 shrink-0 text-brand-600' : 'size-5 shrink-0 text-ink-400 group-hover:text-ink-600'" aria-hidden="true" />
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>

@unless (app(\App\Services\Usage\FeatureGate::class)->allows(auth()->user(), \App\Enums\Feature::PremiumTemplates))
<div class="border-t border-ink-100 p-4">
    <div class="rounded-xl bg-gradient-to-br from-brand-600 to-brand-800 p-4 text-white">
        <p class="text-sm font-semibold">{{ __('dashboard.upsell.title') }}</p>
        <p class="mt-1 text-xs leading-5 text-brand-100">{{ __('dashboard.upsell.body') }}</p>
        <a href="{{ route('billing.index') }}" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-white hover:underline">
            {{ __('billing.see_plans') }}
            <x-heroicon-o-arrow-right class="size-3.5 flip-rtl" aria-hidden="true" />
        </a>
    </div>
</div>
@endunless
