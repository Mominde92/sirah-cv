{{-- All interpolated values are validated by StyleResolver (hex colours, numbers, registered font keys). --}}
@php
    $bodyFont = $fonts->stack($style->bodyFont($rtl), $style->fontBody);
    $headingFont = $fonts->stack($style->headingFont($rtl), $style->fontHeading);
    $margin = $style->pageMarginMm;
    $fs = $style->fontSize;
@endphp
@if ($mode === 'pdf')
@page {
    margin: {!! $margin !!}mm;
    @if ($solidSidebar)
    background: url({!! app(\App\Services\Templates\PageBackground::class)->stripe($palette['primary'], $stripeMm, $stripeOnLeft) !!}) no-repeat;
    background-image-resize: 6;
    @endif
}
body { margin: 0; }
@elseif ($mode === 'print')
@page { size: A4; margin: {!! $margin !!}mm; }
html {
    margin: 0;
    background-color: #ffffff;
    @if ($solidSidebar)
    /* The root background paints the whole page box in Chromium print, margins included. */
    background-image: linear-gradient(to {{ $stripeOnLeft ? 'right' : 'left' }}, {!! $palette['primary'] !!} 0, {!! $palette['primary'] !!} {!! $stripeMm !!}mm, #ffffff {!! $stripeMm !!}mm);
    @endif
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
body { margin: 0; }
@else
html, body { margin: 0; padding: 0; }
body.cv-mode-preview { background: #eef0f4; padding: 24px 0; }
.cv-sheet {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    padding: {!! $margin !!}mm;
    box-sizing: border-box;
    background-color: #ffffff;
    box-shadow: 0 1px 3px rgba(16, 24, 40, .08), 0 12px 32px -12px rgba(16, 24, 40, .18);
    background-image:
        linear-gradient(to bottom, transparent calc(297mm - 1px), #d8dde6 calc(297mm - 1px), #d8dde6 297mm, transparent 297mm)
        @if ($solidSidebar)
        , linear-gradient(to {{ $stripeOnLeft ? 'right' : 'left' }}, {!! $palette['primary'] !!} 0, {!! $palette['primary'] !!} {!! $stripeMm !!}mm, transparent {!! $stripeMm !!}mm)
        @endif
    ;
    background-size: 100% 297mm @if ($solidSidebar), 100% 100% @endif;
    background-repeat: repeat-y @if ($solidSidebar), no-repeat @endif;
}
body.cv-mode-thumbnail { background: #ffffff; padding: 0; overflow: hidden; }
.cv-mode-thumbnail .cv-sheet { box-shadow: none; margin: 0; }
@endif

.cv {
    font-family: {!! $bodyFont !!};
    font-size: {!! $style->pt() !!};
    line-height: {!! $style->lineHeight !!};
    color: {!! $palette['text'] !!};
}
.cv-rtl { direction: rtl; text-align: right; }
.cv a { color: inherit; text-decoration: none; }
.cv p { margin: 0; }
.cv h1, .cv h2, .cv h3 { margin: 0; font-family: {!! $headingFont !!}; }

/* Header */
.cv-header { margin-bottom: {!! $style->sectionSpacing * 0.6 !!}pt; }
.cv-name { font-size: {!! $style->pt(2.25) !!}; font-weight: bold; line-height: 1.15; color: {!! $palette['text'] !!}; }
.cv-headline { font-size: {!! $style->pt(1.12) !!}; color: {!! $palette['primary'] !!}; margin-top: 2pt; }
.cv-contact { font-size: {!! $style->pt(0.9) !!}; color: {!! $palette['secondary'] !!}; margin-top: 6pt; }
.cv-contact-sep { color: {!! $palette['border'] !!}; }
.cv-photo { width: 26mm; height: 26mm; border-radius: 13mm; }
.cv-header-classic { border-bottom: 1.5pt solid {!! $palette['primary'] !!}; padding-bottom: 8pt; }
.cv-header-centered { text-align: center; }
.cv-header-centered .cv-name { letter-spacing: {{ $rtl ? '0' : '0.5pt' }}; }
.cv-header-accent .cv-name { color: {!! $palette['primary'] !!}; }
.cv-header-accent .cv-headline { color: {!! $palette['secondary'] !!}; }
.cv-rule { width: 36pt; height: 3pt; background-color: {!! $palette['accent'] !!}; margin-top: 8pt; font-size: 1pt; line-height: 3pt; }
.cv-header-compact .cv-name { font-size: {!! $style->pt(1.8) !!}; }
.cv-header-compact { border-bottom: 0.75pt solid {!! $palette['border'] !!}; padding-bottom: 6pt; }
.cv-header-split .cv-split-main { float: {{ $start }}; width: 60%; }
.cv-header-split .cv-split-side { float: {{ $end }}; width: 38%; text-align: {{ $end }}; }
.cv-header-split .cv-split-side .cv-contact-line { font-size: {!! $style->pt(0.9) !!}; color: {!! $palette['secondary'] !!}; }
.cv-header-split { border-bottom: 1.5pt solid {!! $palette['primary'] !!}; padding-bottom: 8pt; }
.cv-header-banner { background-color: {!! $palette['primary'] !!}; color: {!! $palette['onPrimary'] !!}; padding: 14pt 16pt; }
.cv-header-banner .cv-name, .cv-header-banner .cv-headline, .cv-header-banner .cv-contact, .cv-header-banner .cv-contact-sep { color: {!! $palette['onPrimary'] !!}; }
.cv-header-photo { float: {{ $end }}; margin-{{ $start }}: 10pt; }
.cv-clear { clear: both; height: 0; font-size: 0; line-height: 0; }

/* Sections */
.cv-section { margin-top: {!! $style->sectionSpacing !!}pt; }
.cv-section-title {
    font-size: {!! $style->pt($style->headingScale) !!};
    font-weight: bold;
    color: {!! $palette['primary'] !!};
    margin-bottom: 5pt;
@switch($style->sectionTitleStyle)
    @case('underline')
    border-bottom: 0.75pt solid {!! $palette['rule'] !!};
    padding-bottom: 3pt;
    @break
    @case('bar')
    border-{{ $start }}: 3pt solid {!! $palette['accent'] !!};
    padding-{{ $start }}: 6pt;
    @break
    @case('caps')
    @unless ($rtl)
    text-transform: uppercase;
    letter-spacing: 1pt;
    font-size: {!! $style->pt($style->headingScale * 0.85) !!};
    @endunless
    border-bottom: 0.75pt solid {!! $palette['border'] !!};
    padding-bottom: 3pt;
    @break
    @case('filled')
    background-color: {!! $palette['tint'] !!};
    padding: 3pt 6pt;
    @break
@endswitch
}
.cv-entry { margin-top: {!! $style->itemSpacing !!}pt; page-break-inside: avoid; }
.cv-entry-first { margin-top: 0; }
.cv-entry-title { font-weight: bold; }
.cv-entry-subtitle { font-weight: normal; }
.cv-entry-meta { font-size: {!! $style->pt(0.9) !!}; color: {!! $palette['secondary'] !!}; }
.cv-entry-desc { margin-top: 2pt; }
.cv-bullets { margin: 3pt 0 0 0; padding-{{ $start }}: 13pt; padding-{{ $end }}: 0; }
.cv-bullets li { margin-top: 1pt; }
.cv-inline-group { margin-top: 2pt; }
.cv-inline-group-name { font-weight: bold; }
.cv-skill { margin-top: 3pt; }
.cv-level { height: 3pt; background-color: {!! $palette['border'] !!}; margin-top: 2pt; font-size: 1pt; line-height: 3pt; }
.cv-level-fill { height: 3pt; background-color: {!! $palette['accent'] !!}; font-size: 1pt; line-height: 3pt; }
.cv-language-level { color: {!! $palette['secondary'] !!}; }

/* Columns (floats: the only multi-column technique mPDF paginates correctly) */
.cv-sidebar { float: {{ $sidebarOnStart ? $start : $end }}; width: {!! $sidebarWidth !!}%; }
.cv-main { float: {{ $sidebarOnStart ? $end : $start }}; width: {!! 100 - $sidebarWidth - $gutter !!}%; }
.cv-sidebar .cv-section-title { font-size: {!! $style->pt(min($style->headingScale, 1.15)) !!}; }
.cv-sidebar-tint .cv-sidebar-inner { background-color: {!! $palette['tint'] !!}; padding: 10pt; }
.cv-sidebar-solid .cv-sidebar-inner { color: {!! $palette['onPrimary'] !!}; }
.cv-sidebar-solid .cv-section-title { color: {!! $palette['onPrimary'] !!}; border-color: {!! $palette['onPrimary'] !!}; background-color: transparent; }
.cv-sidebar-solid .cv-entry-meta, .cv-sidebar-solid .cv-language-level, .cv-sidebar-solid .cv-contact-line { color: {!! $palette['onPrimary'] !!}; }
.cv-sidebar-solid .cv-level { background-color: {!! \App\Support\Color::mix($palette['primary'], $palette['onPrimary'], 0.35) !!}; }
.cv-sidebar-solid .cv-level-fill { background-color: {!! $palette['onPrimary'] !!}; }
.cv-sidebar-photo { text-align: center; margin-bottom: 10pt; }
.cv-sidebar-contacts .cv-contact-line { font-size: {!! $style->pt(0.9) !!}; margin-top: 3pt; }
.cv-contact-line-label { font-weight: bold; }
