@include($headerView, ['showContacts' => true, 'showPhoto' => $doc->photoDataUri !== null])

@foreach ($doc->mainSections() as $section)
    @include($sectionView($section), ['section' => $section, 'inSidebar' => false])
@endforeach
