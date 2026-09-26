@if ($doc->fullName() !== '')
<h1 class="cv-name">{{ $doc->fullName() }}</h1>
@endif
@if (($doc->personal['headline'] ?? '') !== '')
<p class="cv-headline">{{ $doc->personal['headline'] }}</p>
@endif
