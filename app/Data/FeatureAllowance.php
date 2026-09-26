<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\Feature;

/**
 * What a user may do with one feature right now.
 */
final readonly class FeatureAllowance
{
    /**
     * @param  int|null  $limit  NULL means unlimited.
     */
    public function __construct(
        public Feature $feature,
        public bool $enabled,
        public ?int $limit,
        public int $used,
    ) {
    }

    public function isUnlimited(): bool
    {
        return $this->enabled && $this->limit === null;
    }

    public function remaining(): ?int
    {
        if (! $this->enabled) {
            return 0;
        }

        return $this->limit === null ? null : max(0, $this->limit - $this->used);
    }

    public function allows(int $amount = 1): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if ($this->feature->isBoolean() || $this->limit === null) {
            return true;
        }

        return $this->used + $amount <= $this->limit;
    }

    /**
     * Share of the limit consumed, 0-100, for progress bars. NULL when unlimited or boolean.
     */
    public function percentUsed(): ?int
    {
        if (! $this->enabled || $this->feature->isBoolean() || $this->limit === null) {
            return null;
        }

        if ($this->limit === 0) {
            return 100;
        }

        return (int) min(100, round($this->used / $this->limit * 100));
    }
}
