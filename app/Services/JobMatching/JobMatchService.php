<?php

declare(strict_types=1);

namespace App\Services\JobMatching;

use App\Enums\Feature;
use App\Enums\ProcessingStatus;
use App\Enums\SectionType;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\FeatureLimitReached;
use App\Models\JobMatch;
use App\Models\Resume;
use App\Models\User;
use App\Services\Ai\AiAssistant;
use App\Services\Resume\ResumeTextSerializer;
use App\Services\Templates\ResumeDocumentFactory;
use App\Services\Usage\FeatureGate;

/**
 * "Optimize for a job": which of the job's keywords the resume already covers,
 * which are missing, and honest suggestions. Suggestions never propose claiming
 * experience the resume does not show.
 */
final class JobMatchService
{
    public function __construct(
        private readonly KeywordExtractor $keywords,
        private readonly ResumeDocumentFactory $documents,
        private readonly ResumeTextSerializer $serializer,
        private readonly AiAssistant $ai,
        private readonly FeatureGate $gate,
    ) {
    }

    public function analyze(User $user, Resume $resume, string $jobDescription, ?string $jobTitle, ?string $company): JobMatch
    {
        $document = $this->documents->fromResume($resume);
        $resumeText = $this->serializer->toText($document);
        $keywords = $this->keywords->extract(trim(($jobTitle ?? '')."\n".$jobDescription));

        $found = array_values(array_filter($keywords, fn (string $keyword) => $this->keywords->contains($resumeText, $keyword)));
        $missing = array_values(array_diff($keywords, $found));

        $skills = collect($document->sections)
            ->firstWhere('type', SectionType::Skills)?->items ?? [];
        $relevantSkills = collect($skills)
            ->pluck('name')
            ->filter(fn ($skill) => is_string($skill) && $skill !== '' && collect($this->keywords->tokens($skill))->contains(fn (string $token) => $this->keywords->contains($jobDescription, $token)))
            ->values()
            ->all();

        $suggestions = $this->ruleSuggestions($document->sections, $missing, $resume->language);
        $aiUsed = false;

        if ($this->ai->enabled() && $this->gate->allows($user, Feature::AiRequests)) {
            try {
                $aiSuggestions = $this->ai->jobSuggestions($user, $resume, $jobDescription, $found, array_slice($missing, 0, 15));

                if ($aiSuggestions !== []) {
                    $suggestions = array_merge(array_map(fn (string $text) => ['source' => 'ai', 'text' => $text], $aiSuggestions), $suggestions);
                    $aiUsed = true;
                }
            } catch (AiRequestFailed|FeatureLimitReached $exception) {
                report($exception);
            }
        }

        return $user->jobMatches()->create([
            'resume_id' => $resume->id,
            'job_title' => $jobTitle,
            'company' => $company,
            'job_description' => $jobDescription,
            'status' => ProcessingStatus::Completed,
            'resume_version' => $resume->version,
            'analysis' => [
                'keywords' => $keywords,
                'keywords_found' => $found,
                'keywords_missing' => $missing,
                'relevant_skills' => $relevantSkills,
                'coverage' => $keywords === [] ? null : (int) round(count($found) / count($keywords) * 100),
                'suggestions' => $suggestions,
                'ai' => $aiUsed,
            ],
        ]);
    }

    /**
     * @param  list<\App\Data\DocumentSection>  $sections
     * @param  list<string>  $missing
     * @return list<array{source: string, text: string}>
     */
    private function ruleSuggestions(array $sections, array $missing, string $language): array
    {
        $types = array_map(fn ($section) => $section->type, $sections);
        $suggestions = [];

        if (! in_array(SectionType::Summary, $types, true)) {
            $suggestions[] = ['source' => 'rules', 'text' => __('job_match.rules.add_summary', locale: $language)];
        }

        if (! in_array(SectionType::Skills, $types, true)) {
            $suggestions[] = ['source' => 'rules', 'text' => __('job_match.rules.add_skills', locale: $language)];
        }

        if ($missing !== []) {
            $suggestions[] = ['source' => 'rules', 'text' => __('job_match.rules.missing', ['keywords' => implode(', ', array_slice($missing, 0, 8))], $language)];
        }

        $suggestions[] = ['source' => 'rules', 'text' => __('job_match.rules.mirror', locale: $language)];

        return $suggestions;
    }
}
