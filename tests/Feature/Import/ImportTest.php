<?php

namespace Tests\Feature\Import;

use App\Enums\ProcessingStatus;
use App\Enums\ResumeSource;
use App\Models\Resume;
use App\Models\ResumeImport;
use App\Models\User;
use App\Services\Import\HeuristicResumeParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCatalog();
        Storage::fake('local');
    }

    private function docx(array $lines): UploadedFile
    {
        $word = new PhpWord();
        $section = $word->addSection();

        foreach ($lines as $line) {
            $section->addText($line);
        }

        $path = tempnam(sys_get_temp_dir(), 'cv').'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return new UploadedFile($path, 'my-cv.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
    }

    private function sampleCv(): UploadedFile
    {
        return $this->docx([
            'Sara Ahmed',
            'Senior Financial Analyst',
            'sara.ahmed@example.com | +971 50 123 4567 | Dubai, UAE',
            'PROFESSIONAL SUMMARY',
            'Financial analyst with eight years of experience in budgeting, forecasting and reporting for regional banks.',
            'WORK EXPERIENCE',
            'Senior Financial Analyst at Gulf Bank',
            'Jan 2020 - Present',
            '• Built the group forecasting model used by 12 business units',
            '• Reduced month-end close time by 30%',
            'Financial Analyst — Emirates Trading',
            '06/2016 - 12/2019',
            'Prepared monthly management reports.',
            'EDUCATION',
            'Bachelor of Commerce',
            'American University of Sharjah',
            '2012 - 2016',
            'SKILLS',
            'Financial modelling, Excel, Power BI, SQL',
            'LANGUAGES',
            'Arabic (Native), English (Fluent)',
        ]);
    }

    public function test_import_requires_a_plan_with_cv_import(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('imports.store'), ['file' => $this->sampleCv()])
            ->assertSessionHas('upgrade');

        $this->assertSame(0, ResumeImport::count());
    }

    public function test_docx_is_parsed_into_reviewable_data(): void
    {
        $user = $this->userOnPlan('premium');

        $this->actingAs($user)->post(route('imports.store'), ['file' => $this->sampleCv()])->assertRedirect();

        $import = ResumeImport::firstOrFail();
        $this->assertSame(ProcessingStatus::NeedsReview, $import->status);
        $this->assertSame('heuristic', $import->parser);
        $this->assertStringContainsString('Gulf Bank', $import->extracted_text);

        $data = $import->parsed_data;
        $this->assertSame('Sara', $data['personal']['first_name']);
        $this->assertSame('sara.ahmed@example.com', $data['personal']['email']);

        $sections = collect($data['sections'])->keyBy('type');
        $this->assertCount(2, $sections['experience']['items']);
        $this->assertSame('2020-01', $sections['experience']['items'][0]['start_date']);
        $this->assertTrue($sections['experience']['items'][0]['is_current']);
        $this->assertSame('Gulf Bank', $sections['experience']['items'][0]['employer']);
        $this->assertContains('Reduced month-end close time by 30%', $sections['experience']['items'][0]['highlights']);
        $this->assertSame('2016-06', $sections['experience']['items'][1]['start_date']);
        $this->assertSame(['Financial modelling', 'Excel', 'Power BI', 'SQL'], array_column($sections['skills']['items'], 'name'));
        $this->assertSame('native', $sections['languages']['items'][0]['proficiency']);

        // Year-only education dates are flagged instead of silently invented.
        $this->assertTrue(collect($import->review_flags)->contains(fn ($flag) => $flag['reason'] === 'month_assumed'));

        $this->actingAs($user)->get(route('imports.show', $import))->assertOk()->assertSee(__('imports.review.intro_title'));
    }

    public function test_confirming_creates_the_resume_and_keeps_the_original_file(): void
    {
        $user = $this->userOnPlan('premium');
        $this->actingAs($user)->post(route('imports.store'), ['file' => $this->sampleCv()]);
        $import = ResumeImport::firstOrFail();

        $sections = $import->parsed_data['sections'];

        $this->actingAs($user)->post(route('imports.confirm', $import), [
            'title' => 'Imported CV',
            'template_id' => $this->template()->id,
            'language' => 'en',
            'personal' => ['first_name' => 'Sara', 'last_name' => 'Ahmed', 'email' => 'sara.ahmed@example.com'],
            'sections' => $sections,
        ])->assertRedirect();

        $resume = Resume::firstOrFail();
        $this->assertSame(ResumeSource::Import, $resume->source);
        $this->assertSame(ProcessingStatus::Completed, $import->fresh()->status);
        $this->assertSame($resume->id, $import->fresh()->resume_id);
        Storage::disk('local')->assertExists($import->path);
    }

    public function test_files_with_a_wrong_type_are_rejected(): void
    {
        $user = $this->userOnPlan('premium');

        $this->actingAs($user)
            ->post(route('imports.store'), ['file' => UploadedFile::fake()->create('cv.docx', 10, 'text/html')])
            ->assertSessionHasErrors('file');

        $this->actingAs($user)
            ->post(route('imports.store'), ['file' => UploadedFile::fake()->image('cv.png')])
            ->assertSessionHasErrors('file');
    }

    public function test_other_users_cannot_see_an_import(): void
    {
        $user = $this->userOnPlan('premium');
        $this->actingAs($user)->post(route('imports.store'), ['file' => $this->sampleCv()]);

        $this->actingAs(User::factory()->create())->get(route('imports.show', ResumeImport::firstOrFail()))->assertForbidden();
    }

    public function test_parser_handles_arabic_headings(): void
    {
        $result = app(HeuristicResumeParser::class)->parse(implode("\n", [
            'ليلى حداد',
            'مصممة منتجات',
            'layla@example.com',
            'الخبرات العملية',
            'مصممة منتجات أولى لدى نورثويند',
            'مارس 2021 - حتى الآن',
            'المهارات',
            'أبحاث المستخدمين، Figma، أنظمة التصميم',
        ]));

        $this->assertSame('ar', $result['language']);
        $this->assertSame('ليلى', $result['personal']['first_name']);
        $sections = collect($result['sections'])->keyBy('type');
        $this->assertSame('2021-03', $sections['experience']['items'][0]['start_date']);
        $this->assertTrue($sections['experience']['items'][0]['is_current']);
        $this->assertSame(['أبحاث المستخدمين', 'Figma', 'أنظمة التصميم'], array_column($sections['skills']['items'], 'name'));
    }
}
