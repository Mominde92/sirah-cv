@include('resume-templates.partials.section-open')
@foreach ($section->items as $item)
    <p class="cv-skill">{{ $item['name'] }}@if ($item['proficiency'] !== '')<span class="cv-language-level"> — {{ __('resume.proficiency.'.$item['proficiency'], locale: $doc->language) }}</span>@endif</p>
@endforeach
</div>
