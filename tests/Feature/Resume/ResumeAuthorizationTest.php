<?php

namespace Tests\Feature\Resume;

use App\Livewire\Editor\ResumeEditor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResumeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCatalog();
    }

    public function test_users_cannot_touch_someone_elses_resume(): void
    {
        $owner = User::factory()->create();
        $intruder = $this->userOnPlan('premium');
        $resume = $this->createResume($owner);

        $this->actingAs($intruder);

        $this->get(route('resumes.edit', $resume))->assertForbidden();
        $this->get(route('resumes.preview', $resume))->assertForbidden();
        $this->get(route('resumes.photo', $resume))->assertForbidden();
        $this->get(route('resumes.optimize', $resume))->assertForbidden();
        $this->patch(route('resumes.update', $resume), ['title' => 'Hacked'])->assertForbidden();
        $this->post(route('resumes.duplicate', $resume))->assertForbidden();
        $this->delete(route('resumes.destroy', $resume))->assertForbidden();
        $this->postJson(route('resumes.exports.store', $resume), ['format' => 'pdf'])->assertForbidden();

        $this->assertSame('Test resume', $resume->fresh()->title);
        $this->assertNotSoftDeleted($resume);
    }

    public function test_administrators_cannot_open_resume_content(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $resume = $this->createResume($owner);

        $this->actingAs($admin)->get(route('resumes.edit', $resume))->assertForbidden();
        $this->actingAs($admin)->get(route('resumes.preview', $resume))->assertForbidden();
    }

    public function test_livewire_editor_refuses_foreign_resumes(): void
    {
        $resume = $this->createResume(User::factory()->create());

        Livewire::actingAs(User::factory()->create())
            ->test(ResumeEditor::class, ['resume' => $resume])
            ->assertForbidden();
    }

    public function test_resumes_are_addressed_by_uuid_not_numeric_id(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $this->actingAs($user)->get('/resumes/'.$resume->id.'/preview')->assertNotFound();
        $this->actingAs($user)->get('/resumes/'.$resume->uuid.'/preview')->assertOk();
    }
}
