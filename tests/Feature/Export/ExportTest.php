<?php

namespace Tests\Feature\Export;

use App\Enums\Feature;
use App\Enums\ProcessingStatus;
use App\Models\ResumeExport;
use App\Models\User;
use App\Services\Usage\UsageMeter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use ZipArchive;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCatalog();
        Storage::fake('local');
    }

    public function test_pdf_export_is_generated_and_downloadable(): void
    {
        $user = User::factory()->create(['name' => 'Layla Haddad']);
        $resume = $this->createResume($user);

        $response = $this->actingAs($user)->postJson(route('resumes.exports.store', $resume), ['format' => 'pdf'])->assertAccepted();

        // The queue runs synchronously in tests, so the export is already done.
        $status = $this->actingAs($user)->getJson($response->json('status_url'))->assertOk()->assertJsonPath('status', 'completed');
        $export = ResumeExport::firstOrFail();

        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($export->path));
        $this->assertSame(1, app(UsageMeter::class)->used($user, Feature::PdfExport));

        $download = $this->actingAs($user)->get($status->json('download_url'))->assertOk();
        $this->assertStringContainsString('attachment', (string) $download->headers->get('content-disposition'));
        $this->assertNotNull($export->fresh()->downloaded_at);
    }

    public function test_arabic_and_sidebar_templates_render_to_pdf(): void
    {
        $user = $this->userOnPlan('premium');

        foreach (['modern-professional', 'elegant', 'designer', 'executive-ats'] as $slug) {
            $resume = $this->createResume($user, $slug, 'ar');
            $this->actingAs($user)->postJson(route('resumes.exports.store', $resume), ['format' => 'pdf'])->assertAccepted();
        }

        $this->assertSame(4, ResumeExport::where('status', ProcessingStatus::Completed)->count());
    }

    public function test_download_link_must_be_signed_and_owned(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);
        $this->actingAs($user)->postJson(route('resumes.exports.store', $resume), ['format' => 'pdf']);
        $export = ResumeExport::firstOrFail();

        $this->actingAs($user)->get(route('exports.download', $export))->assertForbidden();

        $signed = URL::temporarySignedRoute('exports.download', now()->addMinutes(5), ['export' => $export]);
        $this->actingAs(User::factory()->create())->get($signed)->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson(route('exports.show', $export))->assertForbidden();
    }

    public function test_word_export_requires_a_plan_that_includes_it(): void
    {
        $free = User::factory()->create();
        $resume = $this->createResume($free);

        $this->actingAs($free)->postJson(route('resumes.exports.store', $resume), ['format' => 'docx'])
            ->assertForbidden()
            ->assertJsonPath('feature', 'docx_export');
    }

    public function test_word_export_contains_the_resume_text(): void
    {
        $user = $this->userOnPlan('pro', ['name' => 'Omar Khalid']);
        $resume = $this->createResume($user, 'modern-professional', 'ar');

        $this->actingAs($user)->postJson(route('resumes.exports.store', $resume), ['format' => 'docx'])->assertAccepted();
        $export = ResumeExport::firstOrFail();
        $this->assertSame(ProcessingStatus::Completed, $export->status);

        $path = tempnam(sys_get_temp_dir(), 'docx');
        file_put_contents($path, Storage::disk('local')->get($export->path));
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('Omar', $xml);
        $this->assertStringContainsString('<w:bidi', $xml);
    }

    public function test_free_plan_pdf_quota_is_monthly(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);
        app(UsageMeter::class)->increment($user, Feature::PdfExport, 3);

        $this->actingAs($user)->postJson(route('resumes.exports.store', $resume), ['format' => 'pdf'])->assertForbidden();
    }

    public function test_expired_export_files_are_pruned(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);
        $this->actingAs($user)->postJson(route('resumes.exports.store', $resume), ['format' => 'pdf']);
        $export = ResumeExport::firstOrFail();
        $export->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->artisan('cv:prune-files')->assertSuccessful();

        Storage::disk('local')->assertMissing($export->path);
        $this->assertNull($export->fresh()->path);
    }
}
