<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\ResumeDocument;

interface PdfRenderer
{
    /**
     * Renders the document to PDF bytes (A4).
     */
    public function render(ResumeDocument $document): string;

    /**
     * Short identifier stored with exports for diagnostics.
     */
    public function name(): string;
}
