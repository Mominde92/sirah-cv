<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';
    case Expired = 'expired';

    /**
     * Statuses that still grant access to the plan's features.
     *
     * @return list<self>
     */
    public static function grantingAccess(): array
    {
        return [self::Trialing, self::Active, self::PastDue];
    }

    public function grantsAccess(): bool
    {
        return in_array($this, self::grantingAccess(), true);
    }
}
