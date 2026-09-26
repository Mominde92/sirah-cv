<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use App\Contracts\AiProvider;
use App\Data\AiRequest;
use App\Data\AiResponse;
use App\Exceptions\AiRequestFailed;

/**
 * Anthropic Claude through the official PHP SDK.
 */
final class AnthropicProvider implements AiProvider
{
    private const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

    /**
     * @param  array{api_key: string, model: string, effort: string, fallbacks: bool, timeout: int}  $config
     */
    public function __construct(private readonly array $config)
    {
    }

    public function complete(AiRequest $request): AiResponse
    {
        $content = [];

        foreach ($request->images as $image) {
            $content[] = ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $image['mime'], 'data' => $image['data']]];
        }

        $content[] = ['type' => 'text', 'text' => $request->prompt];

        $outputConfig = ['effort' => $this->config['effort']];

        if ($request->schema !== null) {
            $outputConfig['format'] = ['type' => 'json_schema', 'schema' => $request->schema];
        }

        try {
            $message = $this->client()->beta->messages->create(
                maxTokens: $request->maxTokens,
                messages: [['role' => 'user', 'content' => $content]],
                model: $this->config['model'],
                fallbacks: $this->config['fallbacks'] ? 'default' : null,
                outputConfig: $outputConfig,
                system: $request->system,
                betas: $this->config['fallbacks'] ? [self::FALLBACK_BETA] : null,
            );
        } catch (APIException $exception) {
            throw AiRequestFailed::failed($exception->getMessage());
        }

        if ($message->stopReason === 'refusal') {
            throw AiRequestFailed::refused();
        }

        $text = '';

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        return new AiResponse(
            text: $text,
            model: (string) $message->model,
            inputTokens: (int) $message->usage->inputTokens,
            outputTokens: (int) $message->usage->outputTokens,
        );
    }

    public function name(): string
    {
        return 'anthropic';
    }

    public function model(): string
    {
        return $this->config['model'];
    }

    public function supportsVision(): bool
    {
        return true;
    }

    private function client(): Client
    {
        return new Client(apiKey: $this->config['api_key']);
    }
}
