<?php

namespace Tests\Feature\Admin;

use App\Enums\Feature;
use App\Models\Plan;
use App\Models\User;
use App\Services\Usage\FeatureGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCatalog();
    }

    public function test_regular_users_cannot_access_the_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));

        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->put('/admin/settings', [])->assertForbidden();
    }

    public function test_suspended_admins_lose_access(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->forceFill(['suspended_at' => now()])->save();

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('login'));
    }

    public function test_every_admin_page_renders(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $this->createResume($customer);

        foreach (['/admin', '/admin/users', '/admin/users/'.$customer->id, '/admin/resumes', '/admin/templates', '/admin/templates/create',
            '/admin/templates/classic-ats/edit', '/admin/plans', '/admin/plans/create', '/admin/plans/'.Plan::first()->id.'/edit',
            '/admin/subscriptions', '/admin/payments', '/admin/ai-usage', '/admin/downloads', '/admin/reports', '/admin/settings'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_can_grant_a_plan_and_suspend_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.plan', $user), ['plan_id' => Plan::where('slug', 'premium')->value('id'), 'months' => 3])->assertRedirect();
        $this->assertTrue(app(FeatureGate::class)->allows($user->fresh(), Feature::CvImport));

        $this->actingAs($admin)->post(route('admin.users.suspend', $user))->assertRedirect();
        $this->assertTrue($user->fresh()->isSuspended());

        $this->actingAs($admin)->post(route('admin.users.suspend', $admin))->assertStatus(422);
    }

    public function test_plan_limits_are_editable(): void
    {
        $admin = User::factory()->admin()->create();
        $free = Plan::where('slug', 'free')->firstOrFail();
        $features = [];

        foreach (Feature::cases() as $feature) {
            $features[$feature->value] = ['enabled' => in_array($feature, [Feature::Resumes, Feature::PdfExport], true) ? '1' : '0', 'limit' => $feature === Feature::Resumes ? 5 : 3];
        }

        $this->actingAs($admin)->put(route('admin.plans.update', $free), [
            'slug' => 'free', 'name_en' => 'Free', 'name_ar' => 'مجاني', 'price_monthly' => 0, 'price_yearly' => 0, 'currency' => 'USD',
            'trial_days' => 0, 'sort_order' => 1, 'is_active' => '1', 'is_public' => '1', 'is_default' => '1', 'features' => $features,
        ])->assertSessionHasNoErrors();

        $this->assertSame(5, app(FeatureGate::class)->allowance(User::factory()->create(), Feature::Resumes)->limit);
    }

    public function test_ats_templates_must_stay_single_column(): void
    {
        $admin = User::factory()->admin()->create();
        $template = $this->template('classic-ats');
        $config = $template->configuration;
        $config['layout'] = 'sidebar-left';

        $this->actingAs($admin)->put(route('admin.templates.update', $template), [
            'slug' => 'classic-ats', 'name_en' => 'Classic ATS', 'name_ar' => 'كلاسيكي', 'category' => 'ats', 'ats_status' => 'ats_safe',
            'sort_order' => 1, 'supported_languages' => ['en', 'ar'], 'is_active' => '1', 'is_premium' => '0', 'configuration' => json_encode($config),
        ])->assertSessionHasErrors('ats_status');
    }

    public function test_registration_can_be_closed_from_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.update'), ['general__registration_open' => '0', 'ai__enabled' => '1'])->assertRedirect();
        auth()->logout();

        $this->post('/register', ['name' => 'New', 'email' => 'new@example.com', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1', 'terms' => '1'])
            ->assertSessionHasErrors('email');
    }
}
