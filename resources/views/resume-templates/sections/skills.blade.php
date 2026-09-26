{{--
    ATS templates and the main column list skills as plain text, grouped when groups exist.
    Creative sidebars may add level bars; the level is decoration, the name stays text.
--}}
@php
    $showLevels = $inSidebar && ! $doc->config->atsMode;
    $groups = collect($section->items)->groupBy(fn ($item) => $item['group']);
@endphp
@include('resume-templates.partials.section-open')
@if ($showLevels)
    @foreach ($section->items as $item)
        <div class="cv-skill">
            <p>{{ $item['name'] }}</p>
            @if ($item['level'] !== null)
                <div class="cv-level"><div class="cv-level-fill" style="width: {{ $item['level'] * 20 }}%">&nbsp;</div></div>
            @endif
        </div>
    @endforeach
@elseif ($inSidebar)
    @foreach ($section->items as $item)
        <p class="cv-skill">{{ $item['name'] }}</p>
    @endforeach
@else
    @foreach ($groups as $group => $items)
        <p class="cv-inline-group">@if ($group !== '')<span class="cv-inline-group-name">{{ $group }}: </span>@endif{{ $items->pluck('name')->filter()->implode(' · ') }}</p>
    @endforeach
@endif
</div>
