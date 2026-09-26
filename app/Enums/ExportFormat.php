<?php

declare(strict_types=1);

namespace App\Enums;

enum ExportFormat: string
{
    case Pdf = 'pdf';
    case Docx = 'docx';

    public function extension(): string
    {
        return $this->value;
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::Pdf => 'application/pdf',
            self::Docx => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        };
    }

    public function feature(): Feature
    {
        return match ($this) {
            self::Pdf => Feature::PdfExport,
            self::Docx => Feature::DocxExport,
        };
    }
}
