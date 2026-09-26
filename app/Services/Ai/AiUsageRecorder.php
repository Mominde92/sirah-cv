<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Data\AiResponse;
use App\Enums\AiFeature;
use App\Enums\AiRequestStatus;
use App\Models\AiUsageLog;
use App\Models\Resume;
use App\Models\User;

final class AiUsageRecorder
{
    public function success(User $user, ?Resume $resume, AiFeature $feature, AiProvider $provider, AiResponse $response, int $latencyMs): void
    {
        AiUsageLog::create([
            'user_id' => $user->id,
            'resume_id' => $resume?->id,
            'feature' => $feature,
            'provider' => $provider->name(),
            'model' => $response->model,
            'input_tokens' => $response->inputTokens,
            'output_tokens' => $response->outputTokens,
            'cost_micros' => $this->costMicros($response),
            'latency_ms' => $latencyMs,
            'status' => AiRequestStatus::Succeeded,
        ]);
    }

    public function failure(User $user, ?Resume $resume, AiFeature $feature, AiProvider $provider, string $error, int $latencyMs): void
    {
        AiUsageLog::create([
            'user_id' => $user->id,
            'resume_id' => $resume?->id,
            'feature' => $feature,
            'provider' => $provider->name(),
            'model' => $provider->model(),
            'latency_ms' => $latencyMs,
            'status' => AiRequestStatus::Failed,
            'error' => mb_substr($error, 0, 1000),
        ]);
    }

    private function costMicros(AiResponse $response): ?int
    {
        $price = config('ai.pricing.'.$response->model);

        if (! is_array($price)) {
            return null;
        }

        // USD per million tokens × tokens = micro-dollars.
        return (int) round($response->inputTokens * $price['input'] + $response->outputTokens * $price['output']);
    }
}
