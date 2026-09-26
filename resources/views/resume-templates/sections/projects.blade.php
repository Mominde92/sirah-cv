@include('resume-templates.partials.section-open')
@foreach ($section->items as $item)
    @include('resume-templates.partials.entry', [
        'first' => $loop->first,
        'title' => $item['name'],
        'subtitle' => $item['role'],
        'meta' => [
            $dates->range($item['start_date'], $item['end_date'], false, $doc->language),
            preg_replace('#^https?://(www\.)?#i', '', $item['url']),
        ],
        'description' => $item['description'],
        'highlights' => $item['highlights'],
    ])
@endforeach
</div>
