{{-- Phone numbers, emails and links are always left-to-right, even inside Arabic text. --}}
@php($ltr = in_array($contact['key'], ['email', 'phone', 'website', 'linkedin', 'github'], true))
@if ($ltr)<bdo dir="ltr">@endif{{-- --}}
@if ($contact['href'])<a href="{{ $contact['href'] }}">{{ $contact['text'] }}</a>@else{{ $contact['text'] }}@endif{{-- --}}
@if ($ltr)</bdo>@endif
