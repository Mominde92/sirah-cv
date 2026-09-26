<?php

namespace Tests\Feature\Billing;

use App\Enums\Feature;
use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_three_plans_with_every_feature(): void
    {
        $this->seedPlans();

        $this->assertSame(['free', 'pro', 'premium'], Plan::orderBy('sort_order')->pluck('slug')->all());
        $this->assertSame(1, Plan::where('is_default', true)->count());

        foreach (Plan::with('features')->get() as $plan) {
            $this->assertCount(count(Feature::cases()), $plan->features, "Plan {$plan->slug} is missing features.");
        }
    }

    public function test_it_is_idempotent(): void
    {
        $this->seedPlans();
        $this->seedPlans();

        $this->assertSame(3, Plan::count());
        $this->assertSame(3 * count(Feature::cases()), PlanFeature::count());
    }

    public function test_plan_names_are_translated(): void
    {
        $this->seedPlans();

        $pro = Plan::firstWhere('slug', 'pro');

        $this->assertSame('Pro', $pro->getTranslation('name', 'en'));
        $this->assertSame('احترافي', $pro->getTranslation('name', 'ar'));
    }
}
