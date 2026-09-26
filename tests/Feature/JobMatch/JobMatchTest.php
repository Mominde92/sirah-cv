<?php

namespace Tests\Feature\JobMatch;

use App\Livewire\Editor\ResumeEditor;
use App\Models\JobMatch;
use App\Models\User;
use App\Services\JobMatching\KeywordExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JobMatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_found_and_missing_keywords_are_reported_honestly(): void
    {
        $this->seedCatalog();
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $component = Livewire::actingAs($user)->test(ResumeEditor::class, ['resume' => $resume]);
        $skills = collect($component->get('sections'))->search(fn ($section) => $section['type'] === 'skills');
        $component->call('addItem', $component->get('sections')[$skills]['key']);
        $component->set("sections.{$skills}.items.0.name", 'Power BI');

        $description = str_repeat('We need a financial analyst with Power BI dashboards and financial modelling. Power BI reporting and financial modelling are daily tasks. Experience with SAP is a plus. SAP SAP. ', 2);

        $this->actingAs($user)->post(route('resumes.optimize.store', $resume), ['job_title' => 'Financial Analyst', 'job_description' => $description])->assertRedirect();

        $analysis = JobMatch::firstOrFail()->analysis;
        $this->assertContains('power bi', $analysis['keywords_found']);
        $this->assertContains('sap', $analysis['keywords_missing']);
        $this->assertContains('Power BI', $analysis['relevant_skills']);
        $this->assertNotEmpty($analysis['suggestions']);
        $this->assertFalse($analysis['ai']);

        $this->actingAs($user)->get(route('job-matches.show', JobMatch::firstOrFail()))->assertOk()->assertSee('sap');
        $this->actingAs(User::factory()->create())->get(route('job-matches.show', JobMatch::firstOrFail()))->assertNotFound();
    }

    public function test_arabic_keywords_are_normalised(): void
    {
        $extractor = app(KeywordExtractor::class);

        $this->assertTrue($extractor->contains('خبرة في إدارة المشاريع', 'اداره'));
        // "الإدارة" and "إدارة" normalise to the same term; the stronger phrase wins.
        $keywords = $extractor->extract('مطلوب خبرة في الإدارة المالية. الإدارة المالية أساس الوظيفة. إدارة الفريق.');
        $this->assertContains('اداره ماليه', $keywords);
    }
}
