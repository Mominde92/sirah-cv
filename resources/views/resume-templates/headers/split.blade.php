{{-- Name block first in the markup, contacts second: reading order stays correct for parsers. --}}
<div class="cv-header cv-header-split">
    <div class="cv-split-main">
        @include('resume-templates.headers.partials.identity')
    </div>
    @if ($showContacts && $doc->contacts() !== [])
        <div class="cv-split-side">
            @foreach ($doc->contacts() as $contact)
                <div class="cv-contact-line">@include('resume-templates.partials.contact-value')</div>
            @endforeach
        </div>
    @endif
    <div class="cv-clear"></div>
</div>
