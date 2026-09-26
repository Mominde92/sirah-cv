<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\SectionType;

final readonly class DocumentSection
{
    /**
     * @param  list<array<string, mixed>>  $items  Only items with content.
     */
    public function __construct(
        public SectionType $type,
        public string $title,
        public array $items,
        public string $column,
    ) {
    }
}
