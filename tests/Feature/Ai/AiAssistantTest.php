<?php

namespace Tests\Feature\Ai;

use App\Contracts\AiProvider;
use App\Data\AiRequest;
use App\Data\AiResponse;
use App\Enums\Feature;
use App\Livewire\Editor\ResumeEditor;
use App\Models\AiUsageLog;
use App\Models\User;
use App\Services\Ai\AiManager;
use App\Services\Usage\UsageMeter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<AiRequest> */
    private array $requests = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCatalog();
        config(['ai.default' => 'fake', 'ai.providers.fake.api_key' => 'test']);

        $test = $this;
        app(AiManager::class)->swap(new class($test) implements AiProvider
        {
            public function __construct(private readonly AiAssistantTest $test)
            {
            }

            public function complete(AiRequest $request): AiResponse
            {
                $this->test->record($request);

                return new AiResponse(
                    text: $request->schema !== null ? json_encode(['skills' => ['Budgeting', 'Forecasting']]) : '"Analyst with eight years of experience."',
                    model: 'fake-model',
                    inputTokens: 120,
                    outputTokens: 30,
                );
            }

            public function name(): string
            {
                return 'fake';
            }

            public function model(): string
            {
                return 'fake-model';
            }

            public function supportsVision(): bool
            {
                return false;
            }
        });
    }

    public function record(AiRequest $request): void
    {
        $this->requests[] = $request;
    }

    public function test_summary_suggestion_is_grounded_and_only_applied_on_accept(): void
    {
        $user = $this->userOnPlan('pro');
        $resume = $this->createResume($user);

        $component = Livewire::actingAs($user)->test(ResumeEditor::class, ['resume' => $resume]);
        $summary = collect($component->get('sections'))->firstWhere('type', 'summary');

        $component->call('aiSummary', $summary['key'], $summary['items'][0]['id'])
            ->assertSet('aiSuggestion.value', 'Analyst with eight years of experience.');

        // Nothing is written before the user accepts.
        $this->assertSame('', $resume->fresh()->sections()->where('type', 'summary')->first()->items[0]['content']);
        $this->assertStringContainsString('Never invent', $this->requests[0]->system);

        $component->call('acceptAiSuggestion');
        $this->assertSame('Analyst with eight years of experience.', $resume->sections()->where('type', 'summary')->first()->items[0]['content']);

        $this->assertSame(1, AiUsageLog::count());
        $this->assertSame(1, app(UsageMeter::class)->used($user, Feature::AiRequests));
    }

    public function test_selected_skill_suggestions_are_added(): void
    {
        $user = $this->userOnPlan('pro');
        $resume = $this->createResume($user);
        $component = Livewire::actingAs($user)->test(ResumeEditor::class, ['resume' => $resume]);
        $skills = collect($component->get('sections'))->firstWhere('type', 'skills');

        $component->call('aiSkills', $skills['key'])->call('acceptAiSuggestion', ['Forecasting']);

        $this->assertSame(['Forecasting'], array_column($resume->sections()->where('type', 'skills')->first()->items, 'name'));
    }

    public function test_free_plan_cannot_use_ai(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);
        $component = Livewire::actingAs($user)->test(ResumeEditor::class, ['resume' => $resume]);
        $summary = collect($component->get('sections'))->firstWhere('type', 'summary');

        $component->call('aiSummary', $summary['key'], $summary['items'][0]['id'])
            ->assertSet('aiSuggestion', null)
            ->assertDispatched('notify');

        $this->assertSame([], $this->requests);
    }

    public function test_ai_is_hidden_when_no_provider_is_configured(): void
    {
        config(['ai.default' => null]);
        app(AiManager::class)->swap(null);

        $user = $this->userOnPlan('pro');
        $resume = $this->createResume($user);

        $this->actingAs($user)->get(route('resumes.edit', $resume))->assertOk()->assertDontSee(__('ai.write_summary'));
    }
}
