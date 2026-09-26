<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Services\Admin\SettingsService;
use App\Services\Ai\Providers\AnthropicProvider;
use App\Services\Ai\Providers\GeminiProvider;
use App\Services\Ai\Providers\OpenAiProvider;
use InvalidArgumentException;

/**
 * Resolves the configured provider. AI is considered enabled only when a
 * provider is selected and has credentials.
 */
final class AiManager
{
    private ?AiProvider $resolved = null;

    public function enabled(): bool
    {
        $name = config('ai.default');

        return is_string($name) && $name !== ''
            && filled(config("ai.providers.{$name}.api_key"))
            // Admin kill switch (Admin → Settings), e.g. while a provider has an outage.
            && (bool) app(SettingsService::class)->get('ai.enabled');
    }

    public function provider(): ?AiProvider
    {
        if (! $this->enabled()) {
            return null;
        }

        return $this->resolved ??= $this->make((string) config('ai.default'));
    }

    /**
     * Replaces the provider (tests, or a per-feature override).
     */
    public function swap(?AiProvider $provider): void
    {
        $this->resolved = $provider;
    }

    public function make(string $name): AiProvider
    {
        $config = (array) config("ai.providers.{$name}", []);

        return match ($name) {
            'anthropic' => new AnthropicProvider($config),
            'openai' => new OpenAiProvider($config),
            'gemini' => new GeminiProvider($config),
            default => throw new InvalidArgumentException("Unknown AI provider [{$name}]."),
        };
    }
}
