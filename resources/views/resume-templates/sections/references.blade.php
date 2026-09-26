@include('resume-templates.partials.section-open')
@foreach ($section->items as $item)
    @include('resume-templates.partials.entry', [
        'first' => $loop->first,
        'title' => $item['name'],
        'subtitle' => trim($item['position'].($item['position'] !== '' && $item['company'] !== '' ? ', ' : '').$item['company']),
        'meta' => [$item['relationship'], $item['email'], $item['phone']],
        'description' => '',
        'highlights' => [],
    ])
@endforeach
</div>
