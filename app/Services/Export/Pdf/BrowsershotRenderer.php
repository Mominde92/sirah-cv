<?php

declare(strict_types=1);

namespace App\Services\Export\Pdf;

use App\Contracts\PdfRenderer;
use App\Data\ResumeDocument;
use App\Services\Templates\TemplateRenderer;
use Spatie\Browsershot\Browsershot;

/**
 * Headless-Chromium renderer (PDF_DRIVER=browsershot). Requires Node, Puppeteer and
 * Chromium on the host. JavaScript is disabled: templates are static HTML.
 */
final class BrowsershotRenderer implements PdfRenderer
{
    public function __construct(private readonly TemplateRenderer $templates)
    {
    }

    public function render(ResumeDocument $document): string
    {
        $config = (array) config('cv.pdf.browsershot', []);

        $shot = Browsershot::html($this->templates->render($document, TemplateRenderer::MODE_PRINT))
            ->format('A4')
            ->showBackground()
            ->emulateMedia('print')
            ->setOption('preferCSSPageSize', true)
            ->disableJavascript()
            ->timeout(90);

        if (filled($config['node_binary'] ?? null)) {
            $shot->setNodeBinary($config['node_binary']);
        }

        if (filled($config['npm_binary'] ?? null)) {
            $shot->setNpmBinary($config['npm_binary']);
        }

        if (filled($config['chrome_path'] ?? null)) {
            $shot->setChromePath($config['chrome_path']);
        }

        if ($config['no_sandbox'] ?? false) {
            $shot->noSandbox();
        }

        return $shot->pdf();
    }

    public function name(): string
    {
        return 'browsershot';
    }
}
