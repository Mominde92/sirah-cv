@include('resume-templates.partials.section-open')
@foreach ($section->items as $item)
    @include('resume-templates.partials.entry', [
        'first' => $loop->first,
        'title' => $item['title'],
        'subtitle' => $item['subtitle'],
        'meta' => [$item['date'], preg_replace('#^https?://(www\.)?#i', '', $item['url'])],
        'description' => $item['description'],
        'highlights' => [],
    ])
@endforeach
</div>
