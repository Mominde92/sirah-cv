<?php

namespace Tests\Feature\Billing;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
    }

    private function signed(array $event): array
    {
        $payload = json_encode($event);
        $timestamp = time();

        return [$payload, 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test')];
    }

    private function enableStripe(): void
    {
        config(['services.stripe.secret' => 'sk_test_123', 'services.stripe.webhook_secret' => 'whsec_test']);
    }

    public function test_billing_page_renders_for_free_users(): void
    {
        $this->actingAs(User::factory()->create())->get('/billing')->assertOk()->assertSee(__('billing.page.manual_notice'));
    }

    public function test_webhook_rejects_invalid_signatures(): void
    {
        $this->enableStripe();
        [$payload] = $this->signed(['type' => 'checkout.session.completed']);

        $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => 't='.time().',v1=forged', 'CONTENT_TYPE' => 'application/json'], $payload)
            ->assertStatus(400);
    }

    public function test_checkout_webhook_activates_the_plan_and_records_payment(): void
    {
        $this->enableStripe();
        $user = User::factory()->create();
        $pro = Plan::where('slug', 'pro')->firstOrFail();

        Http::fake(['api.stripe.com/v1/subscriptions/sub_123' => Http::response([
            'id' => 'sub_123',
            'status' => 'active',
            'customer' => 'cus_1',
            'current_period_end' => now()->addMonth()->timestamp,
            'metadata' => ['user_id' => (string) $user->id, 'plan_id' => (string) $pro->id, 'interval' => 'monthly'],
        ])]);

        [$payload, $signature] = $this->signed(['type' => 'checkout.session.completed', 'data' => ['object' => ['mode' => 'subscription', 'subscription' => 'sub_123', 'client_reference_id' => (string) $user->id]]]);
        $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();

        $user->refresh();
        $this->assertSame('pro', $user->subscription->plan->slug);
        $this->assertSame(SubscriptionStatus::Active, $user->subscription->status);

        [$payload, $signature] = $this->signed(['type' => 'invoice.paid', 'data' => ['object' => ['id' => 'in_1', 'subscription' => 'sub_123', 'amount_paid' => 900, 'currency' => 'usd']]]);
        $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();

        $payment = Payment::firstOrFail();
        $this->assertSame(900, $payment->amount);
        $this->assertSame(PaymentStatus::Succeeded, $payment->status);
    }

    public function test_expired_subscriptions_fall_back_to_the_free_plan(): void
    {
        $user = User::factory()->create();
        app(SubscriptionService::class)->grant($user, Plan::where('slug', 'premium')->firstOrFail(), 1);
        Subscription::query()->update(['ends_at' => now()->subDay()]);

        $this->artisan('cv:expire-subscriptions')->assertSuccessful();

        $user->refresh();
        $this->assertNull($user->subscription_id);
        $this->assertSame(SubscriptionStatus::Expired, Subscription::firstOrFail()->status);
    }

    public function test_checkout_without_a_gateway_explains_manual_upgrade(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/billing/checkout', ['plan' => 'pro', 'interval' => 'monthly'])
            ->assertSessionHas('status', __('billing.page.contact_sales'));
    }
}
