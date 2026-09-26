<?php

declare(strict_types=1);

namespace App\Data;

final readonly class AiRequest
{
    /**
     * @param  array<string, mixed>|null  $schema  JSON schema; when set the response text is JSON matching it.
     * @param  list<array{mime: string, data: string}>  $images  Base64 images (vision providers only).
     */
    public function __construct(
        public string $system,
        public string $prompt,
        public int $maxTokens = 2000,
        public ?array $schema = null,
        public array $images = [],
    ) {
    }
}
