<?php

declare(strict_types=1);

namespace App\Services\Import\Extractors;

use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;

/**
 * Extracts the text layer of a PDF. Scanned PDFs (images only) have no text layer;
 * the importer reports that instead of guessing.
 */
final class PdfTextExtractor
{
    private const MAX_PAGES = 12;

    public function extract(string $path): string
    {
        $config = new Config();
        $config->setRetainImageContent(false);
        $config->setDecodeMemoryLimit(64 * 1024 * 1024);

        $document = (new Parser([], $config))->parseFile($path);
        $pages = array_slice($document->getPages(), 0, self::MAX_PAGES);

        $text = implode("\n\n", array_map(fn ($page): string => $page->getText(), $pages));

        return $this->normalise($text);
    }

    /**
     * @return list<string> Font names embedded in the PDF (used by the design analyser).
     */
    public function fonts(string $path): array
    {
        $document = (new Parser())->parseFile($path);

        return array_values(array_unique(array_filter(array_map(
            fn ($font): string => (string) preg_replace('/^[A-Z]{6}\+/', '', (string) $font->getName()),
            $document->getFonts(),
        ))));
    }

    private function normalise(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\u{00A0}", "\f"], ["\n", "\n", ' ', "\n"], $text);
        $text = (string) preg_replace('/[ \t]+/u', ' ', $text);

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $text));
    }
}
