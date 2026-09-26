<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class SubscriptionChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly int $planId,
        public readonly string $status,
    ) {
    }
}
