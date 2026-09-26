<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Contracts\AiProvider;
use App\Data\AiRequest;
use App\Data\AiResponse;
use App\Exceptions\AiRequestFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * OpenAI Chat Completions over HTTP.
 */
final class OpenAiProvider implements AiProvider
{
    /**
     * @param  array{api_key: string, model: string, base_url: string, timeout: int}  $config
     */
    public function __construct(private readonly array $config)
    {
    }

    public function complete(AiRequest $request): AiResponse
    {
        $content = [['type' => 'text', 'text' => $request->prompt]];

        foreach ($request->images as $image) {
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => 'data:'.$image['mime'].';base64,'.$image['data']]];
        }

        $payload = [
            'model' => $this->config['model'],
            'max_completion_tokens' => $request->maxTokens,
            'messages' => [
                ['role' => 'system', 'content' => $request->system],
                ['role' => 'user', 'content' => $content],
            ],
        ];

        if ($request->schema !== null) {
            $payload['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => ['name' => 'result', 'schema' => $request->schema, 'strict' => true],
            ];
        }

        try {
            $response = Http::withToken($this->config['api_key'])
                ->timeout($this->config['timeout'])
                ->retry(2, 500, throw: false)
                ->post(rtrim($this->config['base_url'], '/').'/chat/completions', $payload);
        } catch (ConnectionException $exception) {
            throw AiRequestFailed::failed($exception->getMessage());
        }

        if ($response->failed()) {
            throw AiRequestFailed::failed('HTTP '.$response->status().': '.$response->json('error.message', ''));
        }

        if ($response->json('choices.0.message.refusal')) {
            throw AiRequestFailed::refused();
        }

        return new AiResponse(
            text: (string) $response->json('choices.0.message.content', ''),
            model: (string) $response->json('model', $this->config['model']),
            inputTokens: (int) $response->json('usage.prompt_tokens', 0),
            outputTokens: (int) $response->json('usage.completion_tokens', 0),
        );
    }

    public function name(): string
    {
        return 'openai';
    }

    public function model(): string
    {
        return $this->config['model'];
    }

    public function supportsVision(): bool
    {
        return true;
    }
}
