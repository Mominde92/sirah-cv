<?php

namespace Tests\Feature\Resume;

use App\Livewire\Editor\ResumeEditor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ResumeEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCatalog();
    }

    public function test_editor_page_renders_with_live_preview(): void
    {
        $user = User::factory()->create(['name' => 'Sara Ahmed']);
        $resume = $this->createResume($user);

        $this->actingAs($user)->get(route('resumes.edit', $resume))
            ->assertOk()
            ->assertSee(__('editor.personal.title'))
            ->assertSee('previewFrame', false);
    }

    public function test_changes_autosave_and_bump_the_version(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        Livewire::actingAs($user)
            ->test(ResumeEditor::class, ['resume' => $resume])
            ->set('personal.headline', 'Senior Analyst')
            ->assertSet('saveState', 'saved')
            ->assertDispatched('preview-updated');

        $resume->refresh();
        $this->assertSame('Senior Analyst', $resume->data['personal']['headline']);
        $this->assertSame(2, $resume->version);
    }

    public function test_items_are_sanitised_on_save(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $component = Livewire::actingAs($user)->test(ResumeEditor::class, ['resume' => $resume]);
        $index = collect($component->get('sections'))->search(fn ($section) => $section['type'] === 'experience');

        $component
            ->set("sections.{$index}.items.0.position", '<script>alert(1)</script>Engineer')
            ->set("sections.{$index}.items.0.highlights", "- Shipped v2\n• Led a team of 4\n\n")
            ->set("sections.{$index}.items.0.start_date", 'not-a-date');

        $item = $resume->sections()->where('type', 'experience')->first()->items[0];
        $this->assertSame(['Shipped v2', 'Led a team of 4'], $item['highlights']);
        $this->assertSame('', $item['start_date']);

        // Stored as text; the template escapes it.
        $this->actingAs($user)->get(route('resumes.preview', $resume))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_sections_can_be_added_reordered_hidden_and_removed(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $component = Livewire::actingAs($user)->test(ResumeEditor::class, ['resume' => $resume])
            ->call('addSection', 'certifications');

        $sections = $component->get('sections');
        $new = end($sections);
        $this->assertSame('certifications', $new['type']);

        $component->call('sortSections', $new['key'], 0);
        $this->assertSame('certifications', $resume->sections()->orderBy('position')->first()->type->value);

        $component->call('toggleSection', $new['key']);
        $this->assertFalse($resume->sections()->where('type', 'certifications')->first()->is_visible);

        $component->call('removeSection', $new['key']);
        $this->assertFalse($resume->sections()->where('type', 'certifications')->exists());

        // Singleton sections cannot be added twice.
        $component->call('addSection', 'skills');
        $this->assertSame(1, $resume->sections()->where('type', 'skills')->count());
    }

    public function test_a_stale_editor_does_not_overwrite_newer_changes(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $tabA = Livewire::actingAs($user)->test(ResumeEditor::class, ['resume' => $resume]);
        $tabB = Livewire::actingAs($user)->test(ResumeEditor::class, ['resume' => $resume]);

        $tabA->set('title', 'From tab A')->assertSet('saveState', 'saved');
        $tabB->set('title', 'From tab B')->assertSet('saveState', 'conflict');

        $this->assertSame('From tab A', $resume->fresh()->title);

        $tabB->call('reloadFromServer')->assertSet('title', 'From tab A')->assertSet('saveState', 'saved');
    }

    public function test_advanced_styling_is_locked_on_the_free_plan(): void
    {
        $free = User::factory()->create();
        $resume = $this->createResume($free);

        Livewire::actingAs($free)->test(ResumeEditor::class, ['resume' => $resume])
            ->set('styling.color_primary', '#9f1239')
            ->set('styling.page_margin_mm', 11);

        $styling = $resume->fresh()->styling;
        $this->assertSame('#9f1239', $styling['color_primary']);
        $this->assertArrayNotHasKey('page_margin_mm', $styling);

        $premium = $this->userOnPlan('premium');
        $premiumResume = $this->createResume($premium);

        Livewire::actingAs($premium)->test(ResumeEditor::class, ['resume' => $premiumResume])->set('styling.page_margin_mm', 12);
        $this->assertEquals(12, $premiumResume->fresh()->styling['page_margin_mm']);
    }

    public function test_changing_template_keeps_content(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        Livewire::actingAs($user)->test(ResumeEditor::class, ['resume' => $resume])
            ->set('personal.headline', 'Kept')
            ->call('changeTemplate', $this->template('minimal-ats')->id);

        $resume->refresh();
        $this->assertSame('minimal-ats', $resume->template->slug);
        $this->assertSame('Kept', $resume->data['personal']['headline']);
    }

    public function test_photo_upload_is_reencoded_and_private(): void
    {
        Storage::fake('local');
        $user = $this->userOnPlan('pro');
        $resume = $this->createResume($user, 'modern-professional');

        Livewire::actingAs($user)->test(ResumeEditor::class, ['resume' => $resume])
            ->set('photo', UploadedFile::fake()->image('me.png', 800, 600));

        $path = $resume->fresh()->data['personal']['photo_path'];
        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk('local')->assertExists($path);

        $this->actingAs($user)->get(route('resumes.photo', $resume))->assertOk();
    }

    public function test_versions_can_be_saved_and_restored_on_paid_plans(): void
    {
        $user = $this->userOnPlan('pro');
        $resume = $this->createResume($user);

        $component = Livewire::actingAs($user)->test(ResumeEditor::class, ['resume' => $resume])
            ->set('personal.headline', 'Original')
            ->set('versionLabel', 'Before changes')
            ->call('saveVersion')
            ->set('personal.headline', 'Changed');

        $version = $resume->versions()->firstOrFail();
        $this->assertSame('Before changes', $version->label);

        $component->call('restoreVersion', $version->id)->assertSet('personal.headline', 'Original');
        $this->assertSame('Original', $resume->fresh()->data['personal']['headline']);
    }
}
