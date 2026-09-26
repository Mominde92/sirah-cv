@props(['title', 'subtitle' => null])

<x-layouts.base :title="$title" body-class="bg-white text-ink-800">
    <div class="flex min-h-full">
        <main id="main" class="flex flex-1 flex-col px-4 py-8 sm:px-6 lg:flex-none lg:basis-1/2 lg:px-16 xl:px-24">
            <div class="flex items-center justify-between">
                <x-ui.logo />
                <x-ui.locale-switcher />
            </div>

            <div class="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center py-12">
                <h1 class="text-2xl font-semibold tracking-tight text-ink-900 sm:text-3xl">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-2 text-sm leading-6 text-ink-500">{{ $subtitle }}</p>
                @endif

                <x-ui.session-status class="mt-6" />

                <div class="mt-8">
                    {{ $slot }}
                </div>
            </div>

            <p class="text-center text-xs text-ink-400">&copy; {{ now()->year }} {{ __('common.brand') }}</p>
        </main>

        <aside class="relative hidden flex-1 overflow-hidden bg-brand-950 lg:block" aria-hidden="true">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgb(76_99_242/0.55),transparent_60%)]"></div>
            <div class="absolute inset-0 bg-[linear-gradient(to_right,rgb(255_255_255/0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgb(255_255_255/0.04)_1px,transparent_1px)] bg-[size:48px_48px]"></div>

            <div class="relative flex h-full flex-col justify-center px-16 xl:px-24">
                <div class="relative mx-auto w-full max-w-md">
                    <div class="absolute -top-6 -end-6 w-3/4 rotate-3 rounded-2xl bg-white/10 p-5 ring-1 ring-white/15 backdrop-blur">
                        <div class="h-2 w-1/3 rounded bg-white/40"></div>
                        <div class="mt-3 space-y-1.5">
                            <div class="h-1.5 rounded bg-white/20"></div>
                            <div class="h-1.5 w-5/6 rounded bg-white/20"></div>
                        </div>
                    </div>

                    <div class="relative rounded-2xl bg-white p-7 shadow-2xl">
                        <div class="flex items-center gap-4">
                            <div class="size-12 rounded-full bg-gradient-to-br from-brand-400 to-brand-700"></div>
                            <div class="flex-1 space-y-2">
                                <div class="h-3 w-2/5 rounded bg-ink-800"></div>
                                <div class="h-2 w-3/5 rounded bg-brand-300"></div>
                            </div>
                        </div>
                        <div class="mt-6 space-y-5">
                            @foreach ([['w-1/4', 3], ['w-1/3', 4], ['w-1/5', 2]] as [$width, $lines])
                                <div>
                                    <div class="h-2 {{ $width }} rounded bg-brand-600"></div>
                                    <div class="mt-2.5 space-y-1.5">
                                        @for ($i = 0; $i < $lines; $i++)
                                            <div class="h-1.5 rounded bg-ink-200" style="width: {{ 100 - ($i * 9) }}%"></div>
                                        @endfor
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="absolute -bottom-4 start-6 inline-flex items-center gap-1.5 rounded-full bg-emerald-500 px-3 py-1.5 text-xs font-semibold text-white shadow-lg">
                            <x-heroicon-s-check-badge class="size-4" />
                            {{ __('enums.ats_status.ats_optimized.label') }}
                        </div>
                    </div>
                </div>

                <blockquote class="mx-auto mt-16 max-w-md">
                    <p class="text-xl font-medium leading-8 text-white">{{ __('auth.aside.quote') }}</p>
                    <footer class="mt-4 text-sm text-brand-200">{{ __('auth.aside.caption') }}</footer>
                </blockquote>
            </div>
        </aside>
    </div>
</x-layouts.base>
