@include('resume-templates.partials.section-open')
@foreach ($section->items as $item)
    @include('resume-templates.partials.entry', [
        'first' => $loop->first,
        'title' => $item['name'],
        'subtitle' => $item['issuer'],
        'meta' => [
            $dates->range($item['issued_at'], $item['expires_at'], false, $doc->language),
            $item['credential_id'] !== '' ? __('resume.credential', ['id' => $item['credential_id']], $doc->language) : '',
            preg_replace('#^https?://(www\.)?#i', '', $item['url']),
        ],
        'description' => '',
        'highlights' => [],
    ])
@endforeach
</div>
