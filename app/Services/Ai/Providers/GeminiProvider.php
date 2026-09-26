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
 * Google Gemini generateContent over HTTP.
 */
final class GeminiProvider implements AiProvider
{
    /**
     * @param  array{api_key: string, model: string, base_url: string, timeout: int}  $config
     */
    public function __construct(private readonly array $config)
    {
    }

    public function complete(AiRequest $request): AiResponse
    {
        $parts = [['text' => $request->prompt]];

        foreach ($request->images as $image) {
            $parts[] = ['inline_data' => ['mime_type' => $image['mime'], 'data' => $image['data']]];
        }

        $generation = ['maxOutputTokens' => $request->maxTokens];

        if ($request->schema !== null) {
            $generation['responseMimeType'] = 'application/json';
            $generation['responseJsonSchema'] = $request->schema;
        }

        $url = rtrim($this->config['base_url'], '/').'/models/'.$this->config['model'].':generateContent';

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $this->config['api_key']])
                ->timeout($this->config['timeout'])
                ->retry(2, 500, throw: false)
                ->post($url, [
                    'systemInstruction' => ['parts' => [['text' => $request->system]]],
                    'contents' => [['role' => 'user', 'parts' => $parts]],
                    'generationConfig' => $generation,
                ]);
        } catch (ConnectionException $exception) {
            throw AiRequestFailed::failed($exception->getMessage());
        }

        if ($response->failed()) {
            throw AiRequestFailed::failed('HTTP '.$response->status().': '.$response->json('error.message', ''));
        }

        if (in_array($response->json('candidates.0.finishReason'), ['SAFETY', 'PROHIBITED_CONTENT', 'BLOCKLIST'], true)) {
            throw AiRequestFailed::refused();
        }

        $text = collect((array) $response->json('candidates.0.content.parts', []))->pluck('text')->filter()->implode('');

        return new AiResponse(
            text: $text,
            model: $this->config['model'],
            inputTokens: (int) $response->json('usageMetadata.promptTokenCount', 0),
            outputTokens: (int) $response->json('usageMetadata.candidatesTokenCount', 0),
        );
    }

    public function name(): string
    {
        return 'gemini';
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
