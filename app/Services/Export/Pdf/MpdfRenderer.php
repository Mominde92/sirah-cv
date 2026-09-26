<?php

declare(strict_types=1);

namespace App\Services\Export\Pdf;

use App\Contracts\PdfRenderer;
use App\Data\ResumeDocument;
use App\Services\Templates\FontRegistry;
use App\Services\Templates\TemplateRenderer;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * Pure-PHP PDF renderer. Embeds the same TTF files the preview uses and shapes
 * Arabic through OpenType layout (useOTL), so no system fonts or Chromium are needed.
 */
final class MpdfRenderer implements PdfRenderer
{
    public function __construct(
        private readonly TemplateRenderer $templates,
        private readonly FontRegistry $fonts,
    ) {
    }

    public function render(ResumeDocument $document): string
    {
        $html = $this->templates->render($document, TemplateRenderer::MODE_PDF);
        $defaults = (new ConfigVariables())->getDefaults();
        $fontDefaults = (new FontVariables())->getDefaults();
        $margin = $document->style->pageMarginMm;

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => $margin,
            'margin_right' => $margin,
            'margin_top' => $margin,
            'margin_bottom' => $margin,
            'margin_header' => 0,
            'margin_footer' => 0,
            'tempDir' => $this->tempDir(),
            'fontDir' => array_merge($defaults['fontDir'], [$this->fonts->directory()]),
            'fontdata' => $fontDefaults['fontdata'] + $this->fonts->mpdfFontData(),
            'default_font' => $document->style->bodyFont($document->rtl),
            'directionality' => $document->rtl ? 'rtl' : 'ltr',
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
            // Latin glyphs missing from an Arabic-only font come from the resume's Latin font.
            'useSubstitutions' => true,
            'backupSubsFont' => [$document->style->fontBody, 'dejavusanscondensed'],
            // Tables are never used for layout; this only protects small inline tables.
            'shrink_tables_to_fit' => 1,
            'use_kwt' => true,
        ]);

        // Only our own template HTML is rendered, and it never references remote files.
        $mpdf->curlAllowUnsafeSslRequests = false;
        $mpdf->showImageErrors = false;
        $mpdf->SetTitle($document->fullName() !== '' ? $document->fullName() : $document->title);
        $mpdf->SetAuthor($document->fullName());
        $mpdf->SetCreator((string) config('app.name'));
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    public function name(): string
    {
        return 'mpdf';
    }

    private function tempDir(): string
    {
        $dir = storage_path('framework/cache/mpdf');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }
}
