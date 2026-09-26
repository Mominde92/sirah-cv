@include('resume-templates.partials.section-open')
@foreach ($section->items as $item)
    @include('resume-templates.partials.entry', [
        'first' => $loop->first,
        'title' => $item['title'],
        'subtitle' => $item['publisher'],
        'meta' => [$dates->month($item['date'], $doc->language), preg_replace('#^https?://(www\.)?#i', '', $item['url'])],
        'description' => $item['description'],
        'highlights' => [],
    ])
@endforeach
</div>
