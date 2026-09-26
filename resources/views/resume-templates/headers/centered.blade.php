<div class="cv-header cv-header-centered">
    @if ($showPhoto && $doc->photoDataUri)
        <div style="margin-bottom: 8pt"><img class="cv-photo" src="{{ $doc->photoDataUri }}" alt=""></div>
    @endif
    @include('resume-templates.headers.partials.identity')
    @if ($showContacts)
        @include('resume-templates.headers.partials.contacts')
    @endif
</div>
