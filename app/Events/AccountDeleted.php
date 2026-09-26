<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Carries identifiers only: the user row no longer exists when listeners run.
 */
final class AccountDeleted
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly string $email,
    ) {
    }
}
