<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\PaymentGateway;
use App\Enums\BillingInterval;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Events\SubscriptionChanged;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Owns subscription state. Gateways only report events; this service decides
 * what the user's current subscription is.
 */
final class SubscriptionService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly PlanResolver $plans,
    ) {
    }

    public function gateway(): PaymentGateway
    {
        return $this->gateway;
    }

    /**
     * Makes $plan the user's current subscription, ending any previous one.
     */
    public function activate(
        User $user,
        Plan $plan,
        ?BillingInterval $interval,
        string $provider,
        ?string $providerSubscriptionId = null,
        ?string $providerCustomerId = null,
        ?CarbonInterface $periodEndsAt = null,
        ?CarbonInterface $trialEndsAt = null,
        SubscriptionStatus $status = SubscriptionStatus::Active,
    ): Subscription {
        return DB::transaction(function () use ($user, $plan, $interval, $provider, $providerSubscriptionId, $providerCustomerId, $periodEndsAt, $trialEndsAt, $status): Subscription {
            $existing = $providerSubscriptionId !== null
                ? Subscription::query()->where('provider', $provider)->where('provider_subscription_id', $providerSubscriptionId)->first()
                : null;

            // Close whatever was current before, except the subscription being updated.
            $user->subscriptions()
                ->whereIn('status', SubscriptionStatus::grantingAccess())
                ->when($existing, fn ($query) => $query->whereKeyNot($existing->id))
                ->update(['status' => SubscriptionStatus::Canceled, 'ends_at' => now(), 'canceled_at' => now()]);

            $subscription = $existing ?? new Subscription(['user_id' => $user->id, 'starts_at' => now()]);
            $subscription->fill([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => $status,
                'billing_interval' => $interval,
                'provider' => $provider,
                'provider_subscription_id' => $providerSubscriptionId,
                'provider_customer_id' => $providerCustomerId ?? $subscription->provider_customer_id,
                'current_period_ends_at' => $periodEndsAt,
                'trial_ends_at' => $trialEndsAt,
                'ends_at' => null,
                'canceled_at' => null,
            ])->save();

            $user->forceFill(['subscription_id' => $subscription->id])->save();

            SubscriptionChanged::dispatch($user->id, $plan->id, $status->value);

            return $subscription;
        });
    }

    /**
     * Admin grant (bank transfer, promotion). NULL months = no end date.
     */
    public function grant(User $user, Plan $plan, ?int $months): Subscription
    {
        $endsAt = $months !== null ? now()->addMonths($months) : null;
        $subscription = $this->activate($user, $plan, null, 'manual', periodEndsAt: $endsAt);

        if ($endsAt !== null) {
            $subscription->forceFill(['ends_at' => $endsAt])->save();
        }

        return $subscription;
    }

    public function cancel(Subscription $subscription): void
    {
        $this->gateway->cancel($subscription);

        $subscription->forceFill([
            'canceled_at' => now(),
            // Access continues until the paid period ends.
            'ends_at' => $subscription->current_period_ends_at ?? now(),
        ])->save();
    }

    public function resume(Subscription $subscription): void
    {
        $this->gateway->resume($subscription);

        $subscription->forceFill(['canceled_at' => null, 'ends_at' => null])->save();
    }

    /**
     * Marks subscriptions whose end date passed as expired and clears the user pointer.
     */
    public function expireDue(): int
    {
        $count = 0;

        Subscription::query()
            ->whereIn('status', SubscriptionStatus::grantingAccess())
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->chunkById(200, function ($subscriptions) use (&$count): void {
                foreach ($subscriptions as $subscription) {
                    $subscription->forceFill(['status' => SubscriptionStatus::Expired])->save();
                    User::query()->whereKey($subscription->user_id)->where('subscription_id', $subscription->id)->update(['subscription_id' => null]);
                    SubscriptionChanged::dispatch($subscription->user_id, $subscription->plan_id, SubscriptionStatus::Expired->value);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function recordPayment(
        ?User $user,
        ?Subscription $subscription,
        string $provider,
        string $providerPaymentId,
        int $amount,
        string $currency,
        PaymentStatus $status,
        array $metadata = [],
    ): Payment {
        return Payment::query()->updateOrCreate(
            ['provider' => $provider, 'provider_payment_id' => $providerPaymentId],
            [
                'user_id' => $user?->id,
                'subscription_id' => $subscription?->id,
                'plan_id' => $subscription?->plan_id,
                'amount' => $amount,
                'currency' => strtoupper($currency),
                'status' => $status,
                'paid_at' => $status === PaymentStatus::Succeeded ? now() : null,
                'metadata' => $metadata,
            ],
        );
    }

    public function currentPlan(User $user): Plan
    {
        return $this->plans->forUser($user);
    }
}
