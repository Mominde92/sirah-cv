<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Data\DocumentSection;
use App\Data\ResumeDocument;

/**
 * Plain-text rendering of a resume: the input for AI prompts and keyword matching,
 * and a faithful approximation of what an ATS extracts from the PDF.
 */
final class ResumeTextSerializer
{
    public function __construct(private readonly SectionSchema $schema)
    {
    }

    public function toText(ResumeDocument $document): string
    {
        $lines = array_filter([
            $document->fullName(),
            $document->personal['headline'] ?? '',
            implode(' | ', array_column($document->contacts(), 'text')),
        ]);

        foreach ($document->sections as $section) {
            $lines[] = '';
            $lines[] = mb_strtoupper($section->title);

            foreach ($section->items as $item) {
                $lines[] = $this->item($section, $item);
            }
        }

        return trim(implode("\n", $lines));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function item(DocumentSection $section, array $item): string
    {
        $parts = [];

        foreach ($this->schema->fields($section->type) as $name => $field) {
            $value = $item[$name] ?? null;

            if ($field['type'] === 'checkbox' || $field['type'] === 'level') {
                continue;
            }

            if (is_array($value)) {
                foreach (array_filter($value) as $line) {
                    $parts[] = '- '.$line;
                }
            } elseif (filled($value)) {
                $parts[] = (string) $value;
            }
        }

        return implode("\n", $parts);
    }
}
