@props([
    'title' => null,
    'description' => null,
    'bodyClass' => 'bg-white text-ink-800',
])

@inject('locales', 'App\Services\Localization\LocaleService')

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $locales->direction() }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' · '.__('common.brand') : __('common.brand').' · '.__('common.tagline') }}</title>
    <meta name="description" content="{{ $description ?? __('landing.meta_description') }}">
    <meta name="theme-color" content="#3a48e3">

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ __('common.brand') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    {{ $head ?? '' }}
</head>
<body {{ $attributes->class(['h-full min-h-screen font-sans', $bodyClass]) }}>
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:start-3 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow-lift">
        {{ __('common.skip_to_content') }}
    </a>

    {{ $slot }}

    @livewireScriptConfig
</body>
</html>
