@include('resume-templates.partials.section-open')
@foreach ($section->items as $item)
    <p class="cv-entry-desc">{!! nl2br(e($item['content'])) !!}</p>
@endforeach
</div>
