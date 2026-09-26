<?php

declare(strict_types=1);

namespace App\Services\Billing\Gateways;

use App\Contracts\PaymentGateway;
use App\Enums\BillingInterval;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Stripe Checkout (subscription mode) over the REST API. Plans map to Stripe
 * prices through plans.provider_price_ids = {"stripe": {"monthly": "price_…", "yearly": "price_…"}}.
 * Subscription state is driven by signed webhooks (StripeWebhookController).
 */
final class StripeGateway implements PaymentGateway
{
    private const API = 'https://api.stripe.com/v1';

    public function name(): string
    {
        return 'stripe';
    }

    public function enabled(): bool
    {
        return filled(config('services.stripe.secret')) && filled(config('services.stripe.webhook_secret'));
    }

    public function checkoutUrl(User $user, Plan $plan, BillingInterval $interval): string
    {
        $price = $plan->provider_price_ids['stripe'][$interval->value] ?? null;

        if (! is_string($price) || $price === '') {
            throw new RuntimeException("Plan [{$plan->slug}] has no Stripe price for the {$interval->value} interval.");
        }

        $customer = $user->subscriptions()->where('provider', 'stripe')->whereNotNull('provider_customer_id')->latest()->value('provider_customer_id');

        $payload = [
            'mode' => 'subscription',
            'line_items' => [['price' => $price, 'quantity' => 1]],
            'client_reference_id' => (string) $user->id,
            'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('billing.index'),
            'locale' => 'auto',
            'allow_promotion_codes' => 'true',
            'metadata' => ['user_id' => (string) $user->id, 'plan_id' => (string) $plan->id, 'interval' => $interval->value],
            'subscription_data' => [
                'metadata' => ['user_id' => (string) $user->id, 'plan_id' => (string) $plan->id, 'interval' => $interval->value],
            ] + ($plan->trial_days > 0 ? ['trial_period_days' => $plan->trial_days] : []),
        ];

        $payload += is_string($customer) ? ['customer' => $customer] : ['customer_email' => $user->email];

        $response = $this->client()->asForm()->post(self::API.'/checkout/sessions', $payload);

        if ($response->failed() || ! is_string($response->json('url'))) {
            throw new RuntimeException('Stripe checkout failed: '.$response->json('error.message', 'HTTP '.$response->status()));
        }

        return $response->json('url');
    }

    public function cancel(Subscription $subscription): void
    {
        $this->updateSubscription($subscription, ['cancel_at_period_end' => 'true']);
    }

    public function resume(Subscription $subscription): void
    {
        $this->updateSubscription($subscription, ['cancel_at_period_end' => 'false']);
    }

    /**
     * Verifies the Stripe-Signature header (HMAC-SHA256 over "timestamp.payload").
     */
    public function verifySignature(string $payload, string $header, int $tolerance = 300): bool
    {
        $secret = (string) config('services.stripe.webhook_secret');
        $parts = [];

        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            $parts[$key][] = $value;
        }

        $timestamp = (int) ($parts['t'][0] ?? 0);

        if ($secret === '' || $timestamp === 0 || abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($parts['v1'] ?? [] as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveSubscription(string $id): array
    {
        $response = $this->client()->get(self::API.'/subscriptions/'.$id);

        if ($response->failed()) {
            throw new RuntimeException('Stripe subscription lookup failed.');
        }

        return (array) $response->json();
    }

    /**
     * @param  array<string, string>  $changes
     */
    private function updateSubscription(Subscription $subscription, array $changes): void
    {
        if ($subscription->provider_subscription_id === null) {
            return;
        }

        $response = $this->client()->asForm()->post(self::API.'/subscriptions/'.$subscription->provider_subscription_id, $changes);

        if ($response->failed()) {
            throw new RuntimeException('Stripe update failed: '.$response->json('error.message', 'HTTP '.$response->status()));
        }
    }

    private function client(): PendingRequest
    {
        return Http::withBasicAuth((string) config('services.stripe.secret'), '')
            ->withHeaders(['Stripe-Version' => '2024-06-20'])
            ->timeout(20)
            ->retry(2, 300, throw: false);
    }
}
