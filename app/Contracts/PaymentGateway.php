<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\BillingInterval;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

/**
 * A payment provider. Business logic (SubscriptionService) never talks to a
 * provider directly, so Stripe, Paddle or a regional gateway can be added later.
 */
interface PaymentGateway
{
    public function name(): string;

    /**
     * Whether self-service checkout is available (credentials configured).
     */
    public function enabled(): bool;

    /**
     * Starts checkout and returns the URL to redirect the customer to.
     */
    public function checkoutUrl(User $user, Plan $plan, BillingInterval $interval): string;

    /**
     * Cancels at the end of the current billing period.
     */
    public function cancel(Subscription $subscription): void;

    /**
     * Reverses a pending cancellation.
     */
    public function resume(Subscription $subscription): void;
}
