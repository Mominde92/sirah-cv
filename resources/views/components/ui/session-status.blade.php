{{-- Fortify flashes codes ("password-updated") or already-translated messages; both are handled. --}}
@php
    $status = session('status');
    $message = is_string($status) && \Illuminate\Support\Facades\Lang::has('status.'.$status) ? __('status.'.$status) : $status;
@endphp

@if (is_string($message) && $message !== '')
    <x-ui.alert {{ $attributes }}>{{ $message }}</x-ui.alert>
@endif
