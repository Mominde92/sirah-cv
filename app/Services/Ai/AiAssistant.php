<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Data\AiRequest;
use App\Data\AiResponse;
use App\Enums\AiFeature;
use App\Enums\Feature;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\FeatureLimitReached;
use App\Models\Resume;
use App\Models\User;
use App\Services\Resume\ResumeTextSerializer;
use App\Services\Templates\ResumeDocumentFactory;
use App\Services\Usage\FeatureGate;
use App\Services\Usage\UsageMeter;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Resume writing help. Every prompt is grounded: the model may only rephrase or
 * organise facts that already exist in the user's resume, never invent them.
 * Results are returned as suggestions; nothing is written without the user accepting it.
 */
final class AiAssistant
{
    private const GROUNDING = <<<'TXT'
        You help people write their own resume. Strict rules:
        - Use only facts stated in the resume or text provided. Never invent employers, job titles, dates, degrees, certifications, tools, metrics or achievements.
        - If information is missing, do not fill the gap; keep the statement general instead.
        - Write in the language requested, in a professional, concise, ATS-friendly style. Plain text only: no markdown, no emojis, no quotes around the answer.
        TXT;

    public function __construct(
        private readonly AiManager $ai,
        private readonly FeatureGate $gate,
        private readonly AiUsageRecorder $usage,
        private readonly ResumeDocumentFactory $documents,
        private readonly ResumeTextSerializer $serializer,
        private readonly UsageMeter $meter,
    ) {
    }

    public function enabled(): bool
    {
        return $this->ai->enabled();
    }

    public function generateSummary(User $user, Resume $resume): string
    {
        $response = $this->run($user, $resume, AiFeature::GenerateSummary, new AiRequest(
            system: self::GROUNDING,
            prompt: $this->withResume($resume, 'Write a professional summary of 3 to 4 sentences for the top of this resume. Base it only on the experience, education and skills below.'),
            maxTokens: 600,
        ));

        return $this->clean($response->text);
    }

    public function improveText(User $user, Resume $resume, string $text, string $context): string
    {
        $response = $this->run($user, $resume, AiFeature::ImproveExperience, new AiRequest(
            system: self::GROUNDING,
            prompt: $this->language($resume)."\nImprove the following {$context} description for a resume: clearer, more active and results-oriented. Keep every fact and do not add new ones. Return only the improved text.\n\n<text>\n{$text}\n</text>",
            maxTokens: 800,
        ));

        return $this->clean($response->text);
    }

    public function improveGrammar(User $user, Resume $resume, string $text): string
    {
        $response = $this->run($user, $resume, AiFeature::ImproveGrammar, new AiRequest(
            system: self::GROUNDING,
            prompt: $this->language($resume)."\nFix spelling, grammar and punctuation in the text below. Change nothing else. Return only the corrected text.\n\n<text>\n{$text}\n</text>",
            maxTokens: 800,
        ));

        return $this->clean($response->text);
    }

    /**
     * @param  list<string>  $bullets
     * @return list<string>
     */
    public function rewriteBullets(User $user, Resume $resume, string $role, array $bullets): array
    {
        $response = $this->run($user, $resume, AiFeature::RewriteBullets, new AiRequest(
            system: self::GROUNDING,
            prompt: $this->language($resume)."\nRewrite these resume bullet points for the role \"{$role}\". Start each with a strong action verb, keep every fact and number exactly, add no new claims. Return the same number of bullets or fewer.\n\n<bullets>\n- ".implode("\n- ", $bullets)."\n</bullets>",
            maxTokens: 900,
            schema: $this->listSchema('bullets'),
        ));

        return $this->stringList($response->json()['bullets'] ?? []);
    }

    /**
     * Skills that are evidenced in the resume text but not yet listed in the skills section.
     *
     * @return list<string>
     */
    public function suggestSkills(User $user, Resume $resume): array
    {
        $response = $this->run($user, $resume, AiFeature::SuggestSkills, new AiRequest(
            system: self::GROUNDING,
            prompt: $this->withResume($resume, 'List up to 10 skills that are clearly demonstrated in the experience, projects or education below but are not already in the skills section. Only include a skill if the resume text shows it. Short names only (1 to 4 words).'),
            maxTokens: 500,
            schema: $this->listSchema('skills'),
        ));

        return array_slice($this->stringList($response->json()['skills'] ?? []), 0, 10);
    }

    /**
     * Phrases concrete, honest improvements for a job match. Only suggests using a
     * missing keyword where the resume already shows related experience.
     *
     * @param  list<string>  $found
     * @param  list<string>  $missing
     * @return list<string>
     */
    public function jobSuggestions(User $user, Resume $resume, string $jobDescription, array $found, array $missing): array
    {
        $response = $this->run($user, $resume, AiFeature::MatchJob, new AiRequest(
            system: self::GROUNDING,
            prompt: $this->withResume($resume, "Compare the resume with the job description and give up to 6 specific suggestions to improve how the resume presents the candidate's EXISTING experience for this job. For each missing keyword, only suggest adding it if the resume already shows that experience; otherwise do not mention it. Never suggest claiming experience, tools or certifications the resume does not show.\n\nKeywords already present: ".implode(', ', $found)."\nKeywords missing: ".implode(', ', $missing)."\n\n<job_description>\n".mb_substr($jobDescription, 0, 12000)."\n</job_description>"),
            maxTokens: 1200,
            schema: $this->listSchema('suggestions'),
        ));

        return array_slice($this->stringList($response->json()['suggestions'] ?? []), 0, 6);
    }

    /**
     * Structured extraction for CV import (see ImportService). Returns the model's JSON.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function extract(User $user, AiFeature $feature, string $instructions, string $text, array $schema, array $images = []): array
    {
        return $this->run($user, null, $feature, new AiRequest(
            system: "You extract information from documents. Copy values exactly as written; never guess, complete or invent values. Leave a field empty when the document does not state it.",
            prompt: $instructions.($text !== '' ? "\n\n<document>\n".mb_substr($text, 0, 60000)."\n</document>" : ''),
            maxTokens: 8000,
            schema: $schema,
            images: $images,
        ))->json();
    }

    /**
     * @throws FeatureLimitReached|AiRequestFailed
     */
    private function run(User $user, ?Resume $resume, AiFeature $feature, AiRequest $request): AiResponse
    {
        $provider = $this->ai->provider() ?? throw AiRequestFailed::unavailable();

        $this->gate->authorize($user, Feature::AiRequests);

        if (RateLimiter::tooManyAttempts('ai:'.$user->id, 20)) {
            throw AiRequestFailed::failed('rate limited');
        }

        RateLimiter::hit('ai:'.$user->id, 60);
        $started = hrtime(true);

        try {
            $response = $provider->complete($request);
        } catch (AiRequestFailed $exception) {
            $this->usage->failure($user, $resume, $feature, $provider, (string) ($exception->getPrevious()?->getMessage() ?? $exception->getMessage()), $this->elapsed($started));

            throw $exception;
        }

        $this->usage->success($user, $resume, $feature, $provider, $response, $this->elapsed($started));
        // Checked before the call; counted only once the provider actually answered.
        $this->meter->increment($user, Feature::AiRequests);

        return $response;
    }

    private function withResume(Resume $resume, string $task): string
    {
        $text = $this->serializer->toText($this->documents->fromResume($resume));

        return $this->language($resume)."\n{$task}\n\n<resume>\n".mb_substr($text, 0, 20000)."\n</resume>";
    }

    private function language(Resume $resume): string
    {
        return $resume->language === 'ar'
            ? 'Write the answer in Modern Standard Arabic.'
            : 'Write the answer in English.';
    }

    /**
     * @return array<string, mixed>
     */
    private function listSchema(string $key): array
    {
        return [
            'type' => 'object',
            'properties' => [$key => ['type' => 'array', 'items' => ['type' => 'string']]],
            'required' => [$key],
            'additionalProperties' => false,
        ];
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $values): array
    {
        return array_values(array_filter(array_map(
            fn ($value): string => is_string($value) ? $this->clean($value) : '',
            is_array($values) ? $values : [],
        )));
    }

    private function clean(string $text): string
    {
        $text = trim($text);
        $text = (string) preg_replace('/^["“”\']+|["“”\']+$/u', '', $text);

        return trim((string) preg_replace('/^\s*[-*•]\s+/u', '', $text));
    }

    private function elapsed(int|float $started): int
    {
        return (int) ((hrtime(true) - $started) / 1_000_000);
    }
}
