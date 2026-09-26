@include('resume-templates.partials.section-open')
@foreach ($section->items as $item)
    @include('resume-templates.partials.entry', [
        'first' => $loop->first,
        'title' => $item['role'],
        'subtitle' => $item['organization'],
        'meta' => [$dates->range($item['start_date'], $item['end_date'], false, $doc->language), $item['location']],
        'description' => $item['description'],
        'highlights' => [],
    ])
@endforeach
</div>
