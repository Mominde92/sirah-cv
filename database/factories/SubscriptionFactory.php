<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BillingInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::Active,
            'billing_interval' => BillingInterval::Monthly,
            'provider' => 'manual',
            'starts_at' => now()->subDay(),
            'current_period_ends_at' => now()->addMonth(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Expired,
            'ends_at' => now()->subDay(),
        ]);
    }

    /**
     * Makes the subscription the user's current one.
     */
    public function current(): static
    {
        return $this->afterCreating(function (Subscription $subscription): void {
            $subscription->user->forceFill(['subscription_id' => $subscription->id])->save();
        });
    }
}
