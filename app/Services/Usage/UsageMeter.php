<?php

declare(strict_types=1);

namespace App\Services\Usage;

use App\Enums\Feature;
use App\Models\FeatureUsage;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Monthly counters for metered features (exports, AI requests, imports...).
 */
final class UsageMeter
{
    public function used(User $user, Feature $feature): int
    {
        $this->assertMetered($feature);

        return (int) FeatureUsage::query()
            ->where('user_id', $user->id)
            ->where('feature', $feature)
            ->whereDate('period_start', $this->currentPeriodStart())
            ->value('used');
    }

    /**
     * Atomically adds to the current month's counter, creating it on first use.
     */
    public function increment(User $user, Feature $feature, int $amount = 1): void
    {
        $this->assertMetered($feature);

        if ($amount < 1) {
            throw new InvalidArgumentException('Usage increments must be positive.');
        }

        $now = now();

        DB::table('feature_usages')->upsert(
            [[
                'user_id' => $user->id,
                'feature' => $feature->value,
                'period_start' => $this->currentPeriodStart()->toDateString(),
                'used' => $amount,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['user_id', 'feature', 'period_start'],
            ['used' => DB::raw('used + '.$amount), 'updated_at' => $now],
        );
    }

    public function currentPeriodStart(): CarbonImmutable
    {
        return CarbonImmutable::now()->startOfMonth();
    }

    public function currentPeriodEnd(): CarbonImmutable
    {
        return CarbonImmutable::now()->endOfMonth();
    }

    private function assertMetered(Feature $feature): void
    {
        if (! $feature->isMetered()) {
            throw new InvalidArgumentException("Feature [{$feature->value}] is not metered.");
        }
    }
}
