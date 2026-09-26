<?php

declare(strict_types=1);

namespace App\Services\Import\Extractors;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/**
 * Reads paragraph text from word/document.xml. XML is parsed without network
 * access or entity expansion (no XXE), and the archive entry size is capped.
 */
final class DocxTextExtractor
{
    private const MAX_XML_BYTES = 20 * 1024 * 1024;

    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function extract(string $path): string
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Not a valid DOCX archive.');
        }

        $stat = $zip->statName('word/document.xml');

        if ($stat === false || $stat['size'] > self::MAX_XML_BYTES) {
            $zip->close();

            throw new RuntimeException('The DOCX has no readable document body.');
        }

        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        $dom = new DOMDocument();

        if (! $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT)) {
            throw new RuntimeException('The DOCX body could not be parsed.');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::W);
        $lines = [];

        foreach ($xpath->query('//w:body//w:p') ?: [] as $paragraph) {
            $line = '';

            foreach ($xpath->query('.//w:t | .//w:tab | .//w:br', $paragraph) ?: [] as $node) {
                $line .= match ($node->localName) {
                    'tab' => "\t",
                    'br' => "\n",
                    default => $node->textContent,
                };
            }

            // Word list paragraphs lose their bullet glyph in the XML; restore a marker.
            if ($xpath->query('./w:pPr/w:numPr', $paragraph)?->length) {
                $line = '• '.$line;
            }

            $lines[] = trim($line);
        }

        return trim((string) preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)));
    }
}
