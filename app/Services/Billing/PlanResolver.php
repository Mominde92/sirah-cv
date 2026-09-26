<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * Determines which plan governs a user's limits.
 *
 * A user without a valid subscription falls back to the default (free) plan.
 * Plans are cached with their features; admins invalidate via flush().
 */
final class PlanResolver
{
    private const CACHE_KEY = 'plans.with-features';

    public function __construct(private readonly Cache $cache)
    {
    }

    public function forUser(User $user): Plan
    {
        $subscription = $user->subscription;

        if ($subscription !== null && $subscription->isValid()) {
            $plan = $this->find($subscription->plan_id);

            if ($plan !== null) {
                return $plan;
            }
        }

        return $this->defaultPlan();
    }

    public function defaultPlan(): Plan
    {
        $plan = $this->all()->first(fn (Plan $plan): bool => $plan->is_default && $plan->is_active);

        if ($plan === null) {
            throw new RuntimeException('No active default plan is configured. Run the PlanSeeder.');
        }

        return $plan;
    }

    public function find(int $planId): ?Plan
    {
        return $this->all()->firstWhere('id', $planId);
    }

    /**
     * Active, publicly listed plans in display order.
     *
     * @return Collection<int, Plan>
     */
    public function listed(): Collection
    {
        return $this->all()
            ->filter(fn (Plan $plan): bool => $plan->is_active && $plan->is_public)
            ->sortBy('sort_order')
            ->values();
    }

    /**
     * @return Collection<int, Plan>
     */
    public function all(): Collection
    {
        return $this->cache->rememberForever(
            self::CACHE_KEY,
            fn (): Collection => Plan::query()->with('features')->orderBy('sort_order')->get(),
        );
    }

    public function flush(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }
}
