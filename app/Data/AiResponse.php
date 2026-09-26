<?php

declare(strict_types=1);

namespace App\Data;

final readonly class AiResponse
{
    public function __construct(
        public string $text,
        public string $model,
        public int $inputTokens,
        public int $outputTokens,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function json(): array
    {
        $text = trim($this->text);

        // Tolerate providers that wrap JSON in a code fence.
        if (str_starts_with($text, '```')) {
            $text = (string) preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $text);
        }

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : [];
    }
}
