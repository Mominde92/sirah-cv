{{--
    Two-column body built with floats. A "solid" sidebar moves the name into the
    main column and the photo + contact details to the top of the sidebar.
--}}
@unless ($solidSidebar)
    @include($headerView, ['showContacts' => true, 'showPhoto' => $doc->photoDataUri !== null])
@endunless

<div class="cv-sidebar cv-sidebar-{{ $doc->config->sidebarStyle }}">
    <div class="cv-sidebar-inner">
        @if ($solidSidebar)
            @if ($doc->photoDataUri)
                <div class="cv-sidebar-photo"><img class="cv-photo" src="{{ $doc->photoDataUri }}" alt=""></div>
            @endif
            @if ($doc->contacts() !== [])
                <div class="cv-section cv-sidebar-contacts" style="margin-top: 0">
                    <h2 class="cv-section-title">{{ __('resume.contact', locale: $doc->language) }}</h2>
                    @foreach ($doc->contacts() as $contact)
                        <div class="cv-contact-line">@include('resume-templates.partials.contact-value')</div>
                    @endforeach
                </div>
            @endif
        @endif

        @foreach ($doc->sidebarSections() as $section)
            @include($sectionView($section), ['section' => $section, 'inSidebar' => true])
        @endforeach
    </div>
</div>

<div class="cv-main">
    @if ($solidSidebar)
        @include($headerView, ['showContacts' => false, 'showPhoto' => false])
    @endif

    @foreach ($doc->mainSections() as $section)
        @include($sectionView($section), ['section' => $section, 'inSidebar' => false])
    @endforeach
</div>

<div class="cv-clear"></div>
