{{-- Generic resume entry. Expects: $first, $title, $subtitle, $meta (list), $description, $highlights (list). --}}
@php
    $meta = array_values(array_filter($meta ?? [], fn ($value) => filled($value)));
    $highlights = array_values(array_filter($highlights ?? [], fn ($value) => filled($value)));
@endphp
<div class="cv-entry{{ $first ? ' cv-entry-first' : '' }}">
    @if (filled($title ?? null) || filled($subtitle ?? null))
        <p>@if (filled($title ?? null))<span class="cv-entry-title">{{ $title }}</span>@endif @if (filled($title ?? null) && filled($subtitle ?? null))<span class="cv-entry-subtitle">— </span>@endif @if (filled($subtitle ?? null))<span class="cv-entry-subtitle">{{ $subtitle }}</span>@endif</p>
    @endif
    @if ($meta !== [])
        <p class="cv-entry-meta">{{ implode(' · ', $meta) }}</p>
    @endif
    @if (filled($description ?? null))
        <p class="cv-entry-desc">{!! nl2br(e($description)) !!}</p>
    @endif
    @if ($highlights !== [])
        <ul class="cv-bullets">
            @foreach ($highlights as $highlight)
                <li>{{ $highlight }}</li>
            @endforeach
        </ul>
    @endif
</div>
