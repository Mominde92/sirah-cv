<?php

declare(strict_types=1);

namespace App\Services\Usage;

use App\Data\FeatureAllowance;
use App\Enums\Feature;
use App\Exceptions\FeatureLimitReached;
use App\Models\User;
use App\Services\Billing\PlanResolver;

/**
 * The single entry point for "may this user do X?". Controllers and services
 * never compare limits themselves.
 */
final class FeatureGate
{
    public function __construct(
        private readonly PlanResolver $plans,
        private readonly UsageMeter $meter,
    ) {
    }

    public function allowance(User $user, Feature $feature): FeatureAllowance
    {
        $planFeature = $this->plans->forUser($user)->feature($feature);

        // A feature missing from the plan is treated as disabled, never as unlimited.
        if ($planFeature === null || ! $planFeature->is_enabled) {
            return new FeatureAllowance($feature, false, 0, $this->used($user, $feature));
        }

        return new FeatureAllowance(
            $feature,
            true,
            $feature->isBoolean() ? null : $planFeature->limit_value,
            $this->used($user, $feature),
        );
    }

    public function allows(User $user, Feature $feature, int $amount = 1): bool
    {
        return $this->allowance($user, $feature)->allows($amount);
    }

    /**
     * @throws FeatureLimitReached
     */
    public function authorize(User $user, Feature $feature, int $amount = 1): void
    {
        $allowance = $this->allowance($user, $feature);

        if (! $allowance->allows($amount)) {
            throw new FeatureLimitReached($allowance);
        }
    }

    /**
     * Authorizes a metered action and records it. Call after the action is accepted
     * (e.g. when an export job is queued), so failed validation never consumes quota.
     *
     * @throws FeatureLimitReached
     */
    public function consume(User $user, Feature $feature, int $amount = 1): void
    {
        $this->authorize($user, $feature, $amount);
        $this->meter->increment($user, $feature, $amount);
    }

    /**
     * Every feature's allowance, for the dashboard and billing pages.
     *
     * @return array<string, FeatureAllowance>
     */
    public function summary(User $user): array
    {
        $summary = [];

        foreach (Feature::cases() as $feature) {
            $summary[$feature->value] = $this->allowance($user, $feature);
        }

        return $summary;
    }

    private function used(User $user, Feature $feature): int
    {
        if ($feature->isMetered()) {
            return $this->meter->used($user, $feature);
        }

        return match ($feature) {
            Feature::Resumes => $user->resumes()->count(),
            default => 0,
        };
    }
}
