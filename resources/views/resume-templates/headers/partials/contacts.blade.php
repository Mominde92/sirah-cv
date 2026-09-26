@if ($doc->contacts() !== [])
<p class="cv-contact">@foreach ($doc->contacts() as $contact)@if (! $loop->first)<span class="cv-contact-sep"> | </span>@endif<span>@include('resume-templates.partials.contact-value')</span>@endforeach</p>
@endif
