<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Enums\AiFeature;
use App\Enums\SectionType;
use App\Models\User;
use App\Services\Ai\AiAssistant;
use App\Services\Resume\SectionSchema;

/**
 * AI-assisted extraction (when an AI provider is configured). The model copies
 * values verbatim into a fixed schema and marks what it is unsure about; the
 * output has the same shape as HeuristicResumeParser so the review screen is shared.
 */
final class AiResumeParser
{
    private const TYPES = ['summary', 'experience', 'education', 'skills', 'languages', 'certifications', 'projects', 'achievements', 'awards', 'volunteer', 'publications', 'courses', 'references', 'custom'];

    public function __construct(
        private readonly AiAssistant $ai,
        private readonly SectionSchema $schema,
    ) {
    }

    public function available(): bool
    {
        return $this->ai->enabled();
    }

    /**
     * @return array{language: string, personal: array<string, string>, sections: list<array<string, mixed>>, review_flags: list<array<string, string>>}
     */
    public function parse(User $user, string $text): array
    {
        $result = $this->ai->extract(
            user: $user,
            feature: AiFeature::ParseResume,
            instructions: 'Extract this resume into the schema. Copy text exactly as written in its original language. Dates as YYYY-MM when month and year are both written, otherwise leave them empty and add the field name to "uncertain". Put anything you cannot place confidently in "unplaced". Do not invent, infer or complete any value.',
            text: $text,
            schema: $this->jsonSchema(),
        );

        $flags = [];
        $sections = [];

        foreach ((array) ($result['sections'] ?? []) as $section) {
            $type = SectionType::tryFrom((string) ($section['type'] ?? '')) ?? SectionType::Custom;
            $items = [];

            foreach ((array) ($section['items'] ?? []) as $raw) {
                $items[] = $this->mapItem($type, (array) $raw);

                foreach ((array) ($raw['uncertain'] ?? []) as $field) {
                    $flags[] = ['field' => "sections.{$type->value}", 'reason' => 'ai_uncertain', 'snippet' => trim(($raw['title'] ?? '').' — '.$field, ' —')];
                }
            }

            $sections[] = [
                'type' => $type->value,
                'title' => $type === SectionType::Custom ? (string) ($section['heading'] ?? '') : null,
                'items' => $this->schema->sanitizeItems($type, $items),
            ];
        }

        foreach ((array) ($result['unplaced'] ?? []) as $snippet) {
            $flags[] = ['field' => 'unplaced', 'reason' => 'unplaced', 'snippet' => mb_substr((string) $snippet, 0, 300)];
        }

        $personal = array_map(fn ($value): string => is_string($value) ? $value : '', (array) ($result['personal'] ?? []));

        return [
            'language' => in_array($result['language'] ?? null, ['en', 'ar'], true) ? $result['language'] : 'en',
            'personal' => $personal,
            'sections' => $sections,
            'review_flags' => $flags,
        ];
    }

    /**
     * @param  array<string, mixed>  $raw  Generic extracted entry.
     * @return array<string, mixed>
     */
    private function mapItem(SectionType $type, array $raw): array
    {
        $title = (string) ($raw['title'] ?? '');
        $organization = (string) ($raw['organization'] ?? '');

        return match ($type) {
            SectionType::Summary => ['content' => (string) ($raw['description'] ?? $title)],
            SectionType::Experience => ['position' => $title, 'employer' => $organization] + $this->dated($raw) + ['highlights' => (array) ($raw['highlights'] ?? [])],
            SectionType::Education => ['degree' => $title, 'field' => (string) ($raw['field'] ?? ''), 'institution' => $organization, 'grade' => (string) ($raw['grade'] ?? '')] + $this->dated($raw),
            SectionType::Skills => ['name' => $title, 'group' => (string) ($raw['group'] ?? '')],
            SectionType::Languages => ['name' => $title, 'proficiency' => (string) ($raw['proficiency'] ?? '')],
            SectionType::Certifications => ['name' => $title, 'issuer' => $organization, 'issued_at' => (string) ($raw['start_date'] ?? ''), 'expires_at' => (string) ($raw['end_date'] ?? ''), 'url' => (string) ($raw['url'] ?? '')],
            SectionType::Projects => ['name' => $title, 'role' => $organization, 'url' => (string) ($raw['url'] ?? '')] + $this->dated($raw) + ['highlights' => (array) ($raw['highlights'] ?? [])],
            SectionType::Achievements, SectionType::Awards => ['title' => $title, 'issuer' => $organization, 'date' => (string) ($raw['start_date'] ?? ''), 'description' => (string) ($raw['description'] ?? '')],
            SectionType::Volunteer => ['role' => $title, 'organization' => $organization] + $this->dated($raw),
            SectionType::Publications => ['title' => $title, 'publisher' => $organization, 'date' => (string) ($raw['start_date'] ?? ''), 'url' => (string) ($raw['url'] ?? ''), 'description' => (string) ($raw['description'] ?? '')],
            SectionType::Courses => ['name' => $title, 'institution' => $organization, 'date' => (string) ($raw['start_date'] ?? ''), 'description' => (string) ($raw['description'] ?? '')],
            SectionType::References => ['name' => $title, 'company' => $organization, 'email' => (string) ($raw['email'] ?? ''), 'phone' => (string) ($raw['phone'] ?? '')],
            SectionType::Custom => ['title' => $title, 'subtitle' => $organization, 'description' => (string) ($raw['description'] ?? '')],
        };
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function dated(array $raw): array
    {
        return [
            'location' => (string) ($raw['location'] ?? ''),
            'start_date' => (string) ($raw['start_date'] ?? ''),
            'end_date' => (string) ($raw['end_date'] ?? ''),
            'is_current' => (bool) ($raw['is_current'] ?? false),
            'description' => (string) ($raw['description'] ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonSchema(): array
    {
        $string = ['type' => 'string'];
        $strings = ['type' => 'array', 'items' => $string];
        $item = [
            'type' => 'object',
            'properties' => [
                'title' => $string, 'organization' => $string, 'field' => $string, 'group' => $string, 'grade' => $string,
                'location' => $string, 'start_date' => $string, 'end_date' => $string, 'is_current' => ['type' => 'boolean'],
                'description' => $string, 'highlights' => $strings, 'proficiency' => ['type' => 'string', 'enum' => ['', 'native', 'fluent', 'professional', 'intermediate', 'basic']],
                'url' => $string, 'email' => $string, 'phone' => $string, 'uncertain' => $strings,
            ],
            'additionalProperties' => false,
        ];
        $item['required'] = array_keys($item['properties']);

        $personal = ['type' => 'object', 'properties' => [], 'additionalProperties' => false];
        foreach (['first_name', 'last_name', 'headline', 'email', 'phone', 'location', 'website', 'linkedin', 'github'] as $field) {
            $personal['properties'][$field] = $string;
        }
        $personal['required'] = array_keys($personal['properties']);

        return [
            'type' => 'object',
            'properties' => [
                'language' => ['type' => 'string', 'enum' => ['en', 'ar']],
                'personal' => $personal,
                'sections' => ['type' => 'array', 'items' => [
                    'type' => 'object',
                    'properties' => ['type' => ['type' => 'string', 'enum' => self::TYPES], 'heading' => $string, 'items' => ['type' => 'array', 'items' => $item]],
                    'required' => ['type', 'heading', 'items'],
                    'additionalProperties' => false,
                ]],
                'unplaced' => $strings,
            ],
            'required' => ['language', 'personal', 'sections', 'unplaced'],
            'additionalProperties' => false,
        ];
    }
}
