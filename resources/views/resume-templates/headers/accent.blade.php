<div class="cv-header cv-header-accent">
    @if ($showPhoto && $doc->photoDataUri)
        <div class="cv-header-photo"><img class="cv-photo" src="{{ $doc->photoDataUri }}" alt=""></div>
    @endif
    @include('resume-templates.headers.partials.identity')
    <div class="cv-rule">&nbsp;</div>
    @if ($showContacts)
        @include('resume-templates.headers.partials.contacts')
    @endif
    <div class="cv-clear"></div>
</div>
