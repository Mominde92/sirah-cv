<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
    }

    public function test_landing_page_renders_every_section(): void
    {
        $response = $this->get('/')->assertOk();

        foreach (['templates', 'features', 'ats', 'how-it-works', 'pricing', 'faq'] as $section) {
            $response->assertSee('id="'.$section.'"', false);
        }
    }

    public function test_pricing_lists_public_plans_with_prices_from_the_database(): void
    {
        $this->get('/')
            ->assertSee('Free')
            ->assertSee('Pro')
            ->assertSee('Premium')
            ->assertSee('$9')
            ->assertSee('$19')
            ->assertSee(__('billing.lines.resumes.limited', ['count' => 2]));
    }

    public function test_hidden_and_inactive_plans_are_not_listed(): void
    {
        Plan::firstWhere('slug', 'premium')->update(['is_public' => false]);
        app(\App\Services\Billing\PlanResolver::class)->flush();

        $this->get('/')->assertDontSee(__('landing.pricing.choose', ['plan' => 'Premium']));
    }

    public function test_arabic_landing_page_uses_translated_plan_names(): void
    {
        $this->withSession(['locale' => 'ar'])
            ->get('/')
            ->assertSee('احترافي')
            ->assertSee(__('landing.hero.title', locale: 'ar'));
    }

    public function test_page_does_not_claim_a_numeric_ats_score(): void
    {
        $this->get('/')->assertDontSee('ATS score:')->assertSee(__('landing.ats.honesty'));
    }
}
