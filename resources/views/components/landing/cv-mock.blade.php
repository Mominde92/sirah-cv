{{--
    Illustrative thumbnail of a template's structure for the marketing page.
    Real rendered thumbnails replace these once the Template Engine ships (Phase 4).
--}}
@props(['variant' => 'classic', 'accent' => '#3a48e3'])

@php
    $line = fn (string $width, string $color = 'bg-ink-200') => '<div class="h-[3px] rounded-full '.$color.'" style="width: '.$width.'"></div>';
    $block = function (int $lines, string $titleWidth = '30%') use ($line, $accent): string {
        $html = '<div class="space-y-[5px]"><div class="h-[4px] rounded-full" style="width: '.$titleWidth.'; background: '.$accent.'"></div>';
        for ($i = 0; $i < $lines; $i++) {
            $html .= $line((96 - ($i * 7) % 30).'%');
        }

        return $html.'</div>';
    };
@endphp

<div {{ $attributes->class('relative aspect-[210/297] w-full overflow-hidden rounded-lg bg-white shadow-soft ring-1 ring-ink-200/80') }} aria-hidden="true">
    @switch($variant)
        @case('modern-professional')
            <div class="flex h-full">
                <div class="w-[34%] space-y-4 p-[8%]" style="background: {{ $accent }}">
                    <div class="mx-auto size-8 rounded-full bg-white/80"></div>
                    <div class="space-y-[5px]">{!! $line('70%', 'bg-white/70') !!}{!! $line('90%', 'bg-white/40') !!}{!! $line('60%', 'bg-white/40') !!}</div>
                    <div class="space-y-[5px]">{!! $line('55%', 'bg-white/70') !!}{!! $line('85%', 'bg-white/40') !!}{!! $line('75%', 'bg-white/40') !!}</div>
                </div>
                <div class="flex-1 space-y-4 p-[8%]">
                    <div class="space-y-1.5">{!! $line('65%', 'bg-ink-800') !!}{!! $line('40%', 'bg-ink-300') !!}</div>
                    {!! $block(4) !!}{!! $block(5, '40%') !!}{!! $block(3, '25%') !!}
                </div>
            </div>
            @break

        @case('elegant')
            <div class="flex h-full">
                <div class="flex-1 space-y-4 p-[8%]">
                    <div class="space-y-1.5 border-b border-ink-200 pb-3">{!! $line('60%', 'bg-ink-800') !!}{!! $line('35%', 'bg-ink-400') !!}</div>
                    {!! $block(4) !!}{!! $block(5, '40%') !!}{!! $block(3, '28%') !!}
                </div>
                <div class="w-[30%] space-y-4 bg-ink-50 p-[7%]">
                    {!! $block(3, '60%') !!}{!! $block(4, '50%') !!}
                </div>
            </div>
            @break

        @case('designer')
            <div class="h-[22%] p-[8%]" style="background: {{ $accent }}">
                <div class="flex items-center gap-3">
                    <div class="size-8 shrink-0 rounded-full bg-white/85"></div>
                    <div class="flex-1 space-y-1.5">{!! $line('60%', 'bg-white') !!}{!! $line('35%', 'bg-white/60') !!}</div>
                </div>
            </div>
            <div class="flex">
                <div class="w-[32%] space-y-4 p-[7%]">{!! $block(3, '70%') !!}{!! $block(4, '55%') !!}</div>
                <div class="flex-1 space-y-4 p-[7%]">{!! $block(4) !!}{!! $block(5, '40%') !!}</div>
            </div>
            @break

        @default
            <div @class([
                'space-y-4 p-[9%]',
                'text-center' => in_array($variant, ['executive'], true),
            ])>
                <div @class([
                    'space-y-1.5 pb-3',
                    'border-b-2' => in_array($variant, ['classic', 'professional'], true),
                    'flex flex-col items-center' => $variant === 'executive',
                ]) style="border-color: {{ $accent }}">
                    {!! $line($variant === 'minimal' ? '45%' : '58%', 'bg-ink-800') !!}
                    {!! $line('38%', 'bg-ink-400') !!}
                    @if ($variant === 'modern')
                        <div class="!mt-3 h-[3px] w-10 rounded-full" style="background: {{ $accent }}"></div>
                    @endif
                </div>
                {!! $block(4) !!}{!! $block(5, '38%') !!}{!! $block(3, '26%') !!}{!! $block(3, '32%') !!}
            </div>
    @endswitch
</div>
