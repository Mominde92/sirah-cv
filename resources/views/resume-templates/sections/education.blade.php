@include('resume-templates.partials.section-open')
@foreach ($section->items as $item)
    @include('resume-templates.partials.entry', [
        'first' => $loop->first,
        'title' => trim($item['degree'].($item['degree'] !== '' && $item['field'] !== '' ? ', ' : '').$item['field']),
        'subtitle' => $item['institution'],
        'meta' => [
            $dates->range($item['start_date'], $item['end_date'], false, $doc->language),
            $item['location'],
            $item['grade'] !== '' ? __('resume.grade', ['grade' => $item['grade']], $doc->language) : '',
        ],
        'description' => $item['description'],
        'highlights' => [],
    ])
@endforeach
</div>
