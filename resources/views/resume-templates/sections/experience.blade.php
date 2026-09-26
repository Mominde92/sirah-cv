@include('resume-templates.partials.section-open')
@foreach ($section->items as $item)
    @include('resume-templates.partials.entry', [
        'first' => $loop->first,
        'title' => $item['position'],
        'subtitle' => $item['employer'],
        'meta' => [$dates->range($item['start_date'], $item['end_date'], $item['is_current'], $doc->language), $item['location']],
        'description' => $item['description'],
        'highlights' => $item['highlights'],
    ])
@endforeach
</div>
