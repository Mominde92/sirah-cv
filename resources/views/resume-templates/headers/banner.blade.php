<div class="cv-header cv-header-banner">
    @if ($showPhoto && $doc->photoDataUri)
        <div class="cv-header-photo"><img class="cv-photo" src="{{ $doc->photoDataUri }}" alt=""></div>
    @endif
    @include('resume-templates.headers.partials.identity')
    @if ($showContacts)
        @include('resume-templates.headers.partials.contacts')
    @endif
    <div class="cv-clear"></div>
</div>
