@use('App\Support\Color')
@php
    $rtl = $doc->rtl;
    $start = $rtl ? 'right' : 'left';
    $end = $rtl ? 'left' : 'right';

    $palette = [
        'primary' => $style->colorPrimary,
        'secondary' => $style->colorSecondary,
        'text' => $style->colorText,
        'accent' => $style->colorAccent,
        'rule' => Color::tint($style->colorPrimary, 0.72),
        'tint' => Color::tint($style->colorPrimary, 0.93),
        'border' => Color::tint($style->colorSecondary, 0.7),
        'onPrimary' => Color::readableOn($style->colorPrimary),
    ];

    $sidebarWidth = $doc->config->sidebarWidth;
    $gutter = 5;
    $solidSidebar = $doc->hasSidebar() && $doc->config->sidebarStyle === 'solid';

    // Full-height sidebar colour band, bleeding to the page edge.
    $contentWidthMm = 210 - 2 * $style->pageMarginMm;
    $stripeMm = round($style->pageMarginMm + $contentWidthMm * ($sidebarWidth + $gutter / 2) / 100, 2);
    $sidebarOnStart = ($style->layout === 'sidebar-left');
    // Parenthesised: "=" binds tighter than "xor".
    $stripeOnLeft = ($sidebarOnStart xor $rtl);
@endphp
<!DOCTYPE html>
<html lang="{{ $doc->language }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<title>{{ $doc->fullName() !== '' ? $doc->fullName() : $doc->title }}</title>
<style>
{!! $fontFaces !!}
@include('resume-templates.css')
</style>
</head>
<body class="cv-body cv-mode-{{ $mode }}">
@if (! in_array($mode, ['pdf', 'print'], true))
<div class="cv-sheet">
@endif
<div class="cv{{ $rtl ? ' cv-rtl' : '' }} cv-layout-{{ $style->layout }} cv-tpl-{{ $doc->templateSlug }}">
@include($layoutView)
</div>
@if (! in_array($mode, ['pdf', 'print'], true))
</div>
@endif
</body>
</html>
