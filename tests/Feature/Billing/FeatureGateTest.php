<?php

namespace Tests\Feature\Billing;

use App\Enums\Feature;
use App\Exceptions\FeatureLimitReached;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Usage\FeatureGate;
use App\Services\Usage\UsageMeter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FeatureGateTest extends TestCase
{
    use RefreshDatabase;

    private FeatureGate $gate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
        $this->gate = app(FeatureGate::class);
    }

    public function test_user_without_subscription_gets_the_default_free_plan(): void
    {
        $user = User::factory()->create();

        $allowance = $this->gate->allowance($user, Feature::Resumes);

        $this->assertTrue($allowance->enabled);
        $this->assertSame(2, $allowance->limit);
        $this->assertFalse($this->gate->allows($user, Feature::DocxExport));
    }

    public function test_metered_feature_is_blocked_once_the_monthly_limit_is_used(): void
    {
        $user = User::factory()->create();

        $this->gate->consume($user, Feature::PdfExport);
        $this->gate->consume($user, Feature::PdfExport);
        $this->gate->consume($user, Feature::PdfExport);

        $this->assertFalse($this->gate->allows($user, Feature::PdfExport));

        $this->expectException(FeatureLimitReached::class);
        $this->gate->consume($user, Feature::PdfExport);
    }

    public function test_monthly_counters_reset_in_a_new_month(): void
    {
        $user = User::factory()->create();

        Carbon::setTestNow('2026-03-15 10:00:00');
        app(UsageMeter::class)->increment($user, Feature::PdfExport, 3);
        $this->assertFalse($this->gate->allows($user, Feature::PdfExport));

        Carbon::setTestNow('2026-04-01 00:00:01');
        $this->assertTrue($this->gate->allows($user, Feature::PdfExport));
        $this->assertSame(0, app(UsageMeter::class)->used($user, Feature::PdfExport));

        Carbon::setTestNow();
    }

    public function test_active_subscription_grants_its_plan(): void
    {
        $user = User::factory()->create();
        Subscription::factory()->for($user)->for(Plan::firstWhere('slug', 'pro'))->current()->create();

        $user->refresh();

        $this->assertTrue($this->gate->allowance($user, Feature::Resumes)->isUnlimited());
        $this->assertTrue($this->gate->allows($user, Feature::DocxExport));
        $this->assertSame(100, $this->gate->allowance($user, Feature::AiRequests)->limit);
        $this->assertFalse($this->gate->allows($user, Feature::CvImport));
    }

    public function test_expired_subscription_falls_back_to_the_free_plan(): void
    {
        $user = User::factory()->create();
        Subscription::factory()->for($user)->for(Plan::firstWhere('slug', 'premium'))->expired()->current()->create();

        $user->refresh();

        $this->assertFalse($this->gate->allows($user, Feature::CvImport));
        $this->assertSame(2, $this->gate->allowance($user, Feature::Resumes)->limit);
    }

    public function test_admin_changes_to_limits_apply_after_cache_flush(): void
    {
        $user = User::factory()->create();

        Plan::firstWhere('slug', 'free')->features()->where('feature', Feature::Resumes)->update(['limit_value' => 5]);
        app(\App\Services\Billing\PlanResolver::class)->flush();

        $this->assertSame(5, $this->gate->allowance($user, Feature::Resumes)->limit);
    }

    public function test_feature_missing_from_a_plan_is_treated_as_disabled(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(); // no features at all
        Subscription::factory()->for($user)->for($plan)->current()->create();

        $user->refresh();

        $this->assertFalse($this->gate->allows($user, Feature::PdfExport));
    }

    public function test_limit_exception_renders_as_json_for_api_requests(): void
    {
        $user = User::factory()->create();
        $exception = new FeatureLimitReached($this->gate->allowance($user, Feature::DocxExport));

        $response = $exception->render(request()->duplicate(server: ['HTTP_ACCEPT' => 'application/json']));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('docx_export', $response->getData(true)['feature']);
    }

    public function test_dashboard_shows_usage(): void
    {
        $user = User::factory()->create();
        $this->gate->consume($user, Feature::PdfExport);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(__('dashboard.usage.count', ['used' => 1, 'limit' => 3]));
    }
}
