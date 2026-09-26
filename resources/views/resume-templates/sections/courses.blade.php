@include('resume-templates.partials.section-open')
@foreach ($section->items as $item)
    @include('resume-templates.partials.entry', [
        'first' => $loop->first,
        'title' => $item['name'],
        'subtitle' => $item['institution'],
        'meta' => [$dates->month($item['date'], $doc->language)],
        'description' => $item['description'],
        'highlights' => [],
    ])
@endforeach
</div>
