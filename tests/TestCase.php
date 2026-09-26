<?php

namespace Tests;

use App\Models\Plan;
use App\Models\Resume;
use App\Models\ResumeTemplate;
use App\Models\User;
use App\Services\Billing\SubscriptionService;
use App\Services\Resume\ResumeService;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests must not depend on a built asset manifest.
        $this->withoutVite();
    }

    /**
     * Seeds Free / Pro / Premium, which every page that resolves a plan needs.
     */
    protected function seedPlans(): void
    {
        $this->seed(PlanSeeder::class);
    }

    /**
     * Plans plus the eight starter templates.
     */
    protected function seedCatalog(): void
    {
        $this->seed([PlanSeeder::class, TemplateSeeder::class]);
    }

    protected function userOnPlan(string $slug, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);

        if ($slug !== 'free') {
            app(SubscriptionService::class)->grant($user, Plan::where('slug', $slug)->firstOrFail(), null);
        }

        return $user->fresh();
    }

    protected function template(string $slug = 'classic-ats'): ResumeTemplate
    {
        return ResumeTemplate::where('slug', $slug)->firstOrFail();
    }

    protected function createResume(User $user, string $template = 'classic-ats', string $language = 'en'): Resume
    {
        return app(ResumeService::class)->create($user, 'Test resume', $this->template($template), $language);
    }
}
