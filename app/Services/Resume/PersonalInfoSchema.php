<?php

declare(strict_types=1);

namespace App\Services\Resume;

/**
 * The personal-information block stored in resumes.data["personal"].
 */
final class PersonalInfoSchema
{
    /**
     * @var array<string, array{type: string, max: int}>
     */
    public const FIELDS = [
        'first_name' => ['type' => 'text', 'max' => 80],
        'last_name' => ['type' => 'text', 'max' => 80],
        'headline' => ['type' => 'text', 'max' => 150],
        'email' => ['type' => 'email', 'max' => 190],
        'phone' => ['type' => 'text', 'max' => 40],
        'location' => ['type' => 'text', 'max' => 120],
        'website' => ['type' => 'url', 'max' => 255],
        'linkedin' => ['type' => 'url', 'max' => 255],
        'github' => ['type' => 'url', 'max' => 255],
        'nationality' => ['type' => 'text', 'max' => 80],
        'date_of_birth' => ['type' => 'text', 'max' => 40],
    ];

    public function __construct(private readonly SectionSchema $schema)
    {
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public function sanitize(array $input, ?string $photoPath = null): array
    {
        $clean = [];

        foreach (self::FIELDS as $name => $field) {
            $value = $input[$name] ?? '';

            $clean[$name] = match ($field['type']) {
                'url' => $this->schema->sanitizeUrl($value),
                'email' => is_string($value) && filter_var(trim($value), FILTER_VALIDATE_EMAIL) ? mb_substr(trim($value), 0, $field['max']) : '',
                default => $this->schema->text($value, $field['max']),
            };
        }

        $clean['photo_path'] = $photoPath ?? (is_string($input['photo_path'] ?? null) ? $input['photo_path'] : '');

        return $clean;
    }

    /**
     * @return array<string, string>
     */
    public function empty(): array
    {
        return $this->sanitize([]);
    }
}
