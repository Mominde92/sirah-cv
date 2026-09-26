<?php

declare(strict_types=1);

namespace App\Services\Billing\Gateways;

use App\Contracts\PaymentGateway;
use App\Enums\BillingInterval;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use RuntimeException;

/**
 * No online payments: plans are granted by administrators (bank transfer,
 * invoices, promotions). Cancellation is handled locally by SubscriptionService.
 */
final class ManualGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function enabled(): bool
    {
        return false;
    }

    public function checkoutUrl(User $user, Plan $plan, BillingInterval $interval): string
    {
        throw new RuntimeException('Online checkout is not configured.');
    }

    public function cancel(Subscription $subscription): void
    {
    }

    public function resume(Subscription $subscription): void
    {
    }
}
