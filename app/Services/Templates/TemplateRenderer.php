<?php

declare(strict_types=1);

namespace App\Services\Templates;

use App\Data\DocumentSection;
use App\Data\ResumeDocument;
use Illuminate\Contracts\View\Factory as ViewFactory;

/**
 * Turns a ResumeDocument into one HTML document. The same markup feeds the
 * live preview, the PDF renderers and printing; only the page wrapper differs.
 *
 * View lookup (first match wins):
 *   resume-templates/templates/{slug}/header        → resume-templates/headers/{header_style}
 *   resume-templates/templates/{slug}/sections/{t}  → resume-templates/sections/{t}
 *   resume-templates/layouts/{layout}
 */
final class TemplateRenderer
{
    public const MODE_PREVIEW = 'preview';

    public const MODE_PDF = 'pdf';

    /** Preview without the grey canvas and shadow, for scaled-down cards. */
    public const MODE_THUMBNAIL = 'thumbnail';

    /** Chromium print (Browsershot): @page margins, page-wide backgrounds on the root element. */
    public const MODE_PRINT = 'print';

    public function __construct(
        private readonly ViewFactory $views,
        private readonly FontRegistry $fonts,
        private readonly DateFormatter $dates,
    ) {
    }

    public function render(ResumeDocument $document, string $mode = self::MODE_PREVIEW): string
    {
        $style = $document->style;
        $fontKeys = [$style->bodyFont($document->rtl), $style->headingFont($document->rtl), $style->fontBody, $style->fontHeading];

        return $this->views->make('resume-templates.document', [
            'doc' => $document,
            'style' => $style,
            'mode' => $mode,
            'dates' => $this->dates,
            'fonts' => $this->fonts,
            // mPDF embeds fonts itself; every browser-based mode loads them with @font-face.
            'fontFaces' => $mode !== self::MODE_PDF ? $this->fonts->fontFaceCss($fontKeys) : '',
            'headerView' => $this->headerView($document),
            'sectionView' => fn (DocumentSection $section): string => $this->sectionView($document, $section),
            'layoutView' => 'resume-templates.layouts.'.$style->layout,
        ])->render();
    }

    private function headerView(ResumeDocument $document): string
    {
        $override = 'resume-templates.templates.'.$document->templateSlug.'.header';

        return $this->views->exists($override) ? $override : 'resume-templates.headers.'.$document->style->headerStyle;
    }

    private function sectionView(ResumeDocument $document, DocumentSection $section): string
    {
        $override = 'resume-templates.templates.'.$document->templateSlug.'.sections.'.$section->type->value;

        return $this->views->exists($override) ? $override : 'resume-templates.sections.'.$section->type->value;
    }
}
