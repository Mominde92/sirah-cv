<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BillingInterval;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\Gateways\StripeGateway;
use App\Services\Billing\SubscriptionService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Stripe webhooks: the only source of truth for paid subscription state.
 * Requests without a valid signature are rejected before anything is read.
 */
final class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeGateway $stripe, SubscriptionService $subscriptions): Response
    {
        $payload = $request->getContent();

        if (! $stripe->enabled() || ! $stripe->verifySignature($payload, (string) $request->header('Stripe-Signature'))) {
            return response('Invalid signature', 400);
        }

        $event = json_decode($payload, true);
        $object = (array) ($event['data']['object'] ?? []);

        match ($event['type'] ?? null) {
            'checkout.session.completed' => $this->checkoutCompleted($object, $stripe, $subscriptions),
            'customer.subscription.updated', 'customer.subscription.created' => $this->subscriptionUpdated($object, $subscriptions),
            'customer.subscription.deleted' => $this->subscriptionDeleted($object),
            'invoice.paid', 'invoice.payment_succeeded' => $this->invoice($object, $subscriptions, PaymentStatus::Succeeded),
            'invoice.payment_failed' => $this->invoice($object, $subscriptions, PaymentStatus::Failed),
            default => null,
        };

        return response('ok');
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function checkoutCompleted(array $session, StripeGateway $stripe, SubscriptionService $subscriptions): void
    {
        if (($session['mode'] ?? null) !== 'subscription' || ! is_string($session['subscription'] ?? null)) {
            return;
        }

        $this->subscriptionUpdated($stripe->retrieveSubscription($session['subscription']), $subscriptions, $session['client_reference_id'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function subscriptionUpdated(array $object, SubscriptionService $subscriptions, ?string $userId = null): void
    {
        $metadata = (array) ($object['metadata'] ?? []);
        $user = User::query()->find($metadata['user_id'] ?? $userId);
        $plan = Plan::query()->find($metadata['plan_id'] ?? null);

        if ($user === null || $plan === null) {
            return;
        }

        $status = match ($object['status'] ?? null) {
            'trialing' => SubscriptionStatus::Trialing,
            'active' => SubscriptionStatus::Active,
            'past_due', 'unpaid' => SubscriptionStatus::PastDue,
            'canceled', 'incomplete_expired' => SubscriptionStatus::Canceled,
            default => null,
        };

        if ($status === null) {
            return;
        }

        $periodEnd = $object['current_period_end'] ?? $object['items']['data'][0]['current_period_end'] ?? null;

        $subscription = $subscriptions->activate(
            user: $user,
            plan: $plan,
            interval: BillingInterval::tryFrom((string) ($metadata['interval'] ?? '')),
            provider: 'stripe',
            providerSubscriptionId: (string) $object['id'],
            providerCustomerId: is_string($object['customer'] ?? null) ? $object['customer'] : null,
            periodEndsAt: is_numeric($periodEnd) ? CarbonImmutable::createFromTimestamp((int) $periodEnd) : null,
            trialEndsAt: is_numeric($object['trial_end'] ?? null) ? CarbonImmutable::createFromTimestamp((int) $object['trial_end']) : null,
            status: $status,
        );

        if (($object['cancel_at_period_end'] ?? false) === true) {
            $subscription->forceFill(['canceled_at' => now(), 'ends_at' => $subscription->current_period_ends_at])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function subscriptionDeleted(array $object): void
    {
        Subscription::query()
            ->where('provider', 'stripe')
            ->where('provider_subscription_id', $object['id'] ?? '')
            ->update(['status' => SubscriptionStatus::Canceled, 'ends_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $invoice
     */
    private function invoice(array $invoice, SubscriptionService $subscriptions, PaymentStatus $status): void
    {
        $subscription = Subscription::query()->where('provider', 'stripe')->where('provider_subscription_id', $invoice['subscription'] ?? '')->first();

        $subscriptions->recordPayment(
            user: $subscription?->user,
            subscription: $subscription,
            provider: 'stripe',
            providerPaymentId: (string) ($invoice['id'] ?? ''),
            amount: (int) ($invoice['amount_paid'] ?? $invoice['amount_due'] ?? 0),
            currency: (string) ($invoice['currency'] ?? 'usd'),
            status: $status,
            metadata: ['invoice_url' => $invoice['hosted_invoice_url'] ?? null, 'number' => $invoice['number'] ?? null],
        );
    }
}
