<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Resume section types. Personal information is not a section: it lives in resumes.data.
 */
enum SectionType: string
{
    case Summary = 'summary';
    case Experience = 'experience';
    case Education = 'education';
    case Skills = 'skills';
    case Languages = 'languages';
    case Certifications = 'certifications';
    case Projects = 'projects';
    case Achievements = 'achievements';
    case Awards = 'awards';
    case Volunteer = 'volunteer';
    case Publications = 'publications';
    case Courses = 'courses';
    case References = 'references';
    case Custom = 'custom';

    /**
     * Standard, ATS-recognisable section heading in the given locale.
     */
    public function defaultTitle(?string $locale = null): string
    {
        return __('sections.'.$this->value, locale: $locale);
    }

    /**
     * Whether the section holds a single free-text entry rather than a list.
     */
    public function isSingleEntry(): bool
    {
        return $this === self::Summary;
    }

    /**
     * Whether a resume may contain more than one section of this type.
     */
    public function allowsMultiple(): bool
    {
        return $this === self::Custom;
    }

    /**
     * Sections created for a brand-new resume, in display order.
     *
     * @return list<self>
     */
    public static function defaults(): array
    {
        return [self::Summary, self::Experience, self::Education, self::Skills, self::Languages];
    }
}
