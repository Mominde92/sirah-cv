<?php

namespace Tests\Feature\Resume;

use App\Enums\ResumeSource;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCatalog();
    }

    public function test_resume_pages_render(): void
    {
        $user = User::factory()->create();
        $this->createResume($user);

        $this->actingAs($user)->get('/resumes')->assertOk()->assertSee('Test resume');
        $this->actingAs($user)->get('/resumes/create')->assertOk()->assertSee(__('resumes.create.choose_template'));
        $this->actingAs($user)->get('/templates')->assertOk();
        $this->get('/templates')->assertOk();
    }

    public function test_user_can_create_a_resume_with_default_sections(): void
    {
        $user = User::factory()->create(['name' => 'Sara Ahmed']);

        $response = $this->actingAs($user)->post('/resumes', [
            'title' => 'Finance roles',
            'template_id' => $this->template()->id,
            'language' => 'ar',
        ]);

        $resume = Resume::firstOrFail();
        $response->assertRedirect(route('resumes.edit', $resume));

        $this->assertSame('Finance roles', $resume->title);
        $this->assertSame('ar', $resume->language);
        $this->assertSame('rtl', $resume->direction());
        $this->assertSame('Sara', $resume->data['personal']['first_name']);
        $this->assertSame(['summary', 'experience', 'education', 'skills', 'languages'], $resume->sections->pluck('type')->map->value->all());
    }

    public function test_free_plan_resume_limit_is_enforced(): void
    {
        $user = User::factory()->create();
        $this->createResume($user);
        $this->createResume($user);

        $this->actingAs($user)
            ->post('/resumes', ['title' => 'Third', 'template_id' => $this->template()->id, 'language' => 'en'])
            ->assertSessionHas('upgrade');

        $this->assertSame(2, $user->resumes()->count());
    }

    public function test_premium_templates_require_a_paid_plan(): void
    {
        $free = User::factory()->create();

        $this->actingAs($free)
            ->post('/resumes', ['title' => 'Creative', 'template_id' => $this->template('designer')->id, 'language' => 'en'])
            ->assertSessionHasErrors('template_id');

        $pro = $this->userOnPlan('pro');

        $this->actingAs($pro)
            ->post('/resumes', ['title' => 'Creative', 'template_id' => $this->template('designer')->id, 'language' => 'en'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $pro->resumes()->count());
    }

    public function test_resume_can_be_renamed(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $this->actingAs($user)->patch(route('resumes.update', $resume), ['title' => 'Renamed'])->assertRedirect();

        $this->assertSame('Renamed', $resume->fresh()->title);
    }

    public function test_resume_can_be_duplicated(): void
    {
        $user = $this->userOnPlan('pro');
        $resume = $this->createResume($user, 'modern-professional');

        $this->actingAs($user)->post(route('resumes.duplicate', $resume))->assertRedirect(route('resumes.index'));

        $copy = Resume::whereKeyNot($resume->id)->firstOrFail();
        $this->assertSame($resume->id, $copy->parent_id);
        $this->assertSame(ResumeSource::Duplicate, $copy->source);
        $this->assertSame($resume->sections->count(), $copy->sections()->count());
        $this->assertNotSame($resume->uuid, $copy->uuid);
        $this->assertNotSame($resume->sections->first()->items[0]['id'], $copy->sections()->first()->items[0]['id']);
    }

    public function test_duplicating_respects_the_resume_limit(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);
        $this->createResume($user);

        $this->actingAs($user)->post(route('resumes.duplicate', $resume))->assertSessionHas('upgrade');
        $this->assertSame(2, $user->resumes()->count());
    }

    public function test_resume_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $this->actingAs($user)->delete(route('resumes.destroy', $resume))->assertRedirect(route('resumes.index'));

        $this->assertSoftDeleted($resume);
    }

    public function test_preview_renders_the_resume_with_a_strict_csp(): void
    {
        $user = User::factory()->create(['name' => 'Layla Haddad']);
        $resume = $this->createResume($user);

        $this->actingAs($user)->get(route('resumes.preview', $resume))
            ->assertOk()
            ->assertSee('Layla Haddad')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_every_template_preview_renders_in_both_languages(): void
    {
        foreach (\App\Models\ResumeTemplate::all() as $template) {
            foreach (['en', 'ar'] as $language) {
                $this->get(route('templates.preview', ['template' => $template, 'lang' => $language]))
                    ->assertOk()
                    ->assertSee($language === 'ar' ? 'dir="rtl"' : 'dir="ltr"', false);
            }
        }
    }
}
