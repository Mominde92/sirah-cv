<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Enums\SectionType;

/**
 * Common English and Arabic resume headings mapped to section types.
 */
final class SectionHeadings
{
    /**
     * @var array<string, list<string>>
     */
    private const HEADINGS = [
        'summary' => ['summary', 'professional summary', 'profile', 'professional profile', 'about me', 'about', 'objective', 'career objective', 'personal statement', 'overview',
            'الملخص', 'الملخص المهني', 'نبذة', 'نبذة عني', 'نبذة مختصرة', 'الهدف الوظيفي', 'الملف الشخصي', 'ملخص'],
        'experience' => ['experience', 'work experience', 'professional experience', 'employment', 'employment history', 'work history', 'career history', 'relevant experience',
            'الخبرات', 'الخبرة', 'الخبرات العملية', 'الخبرة العملية', 'الخبرات المهنية', 'الخبرة المهنية', 'السجل الوظيفي', 'الخبرات الوظيفية'],
        'education' => ['education', 'academic background', 'academic qualifications', 'qualifications', 'education and training', 'academic history',
            'التعليم', 'المؤهلات', 'المؤهلات العلمية', 'المؤهل العلمي', 'التحصيل العلمي', 'الدراسة'],
        'skills' => ['skills', 'key skills', 'technical skills', 'core competencies', 'competencies', 'areas of expertise', 'expertise', 'core skills', 'skills and abilities',
            'المهارات', 'المهارات الفنية', 'المهارات التقنية', 'الكفاءات', 'مجالات الخبرة', 'المهارات الأساسية'],
        'languages' => ['languages', 'language skills', 'اللغات', 'المهارات اللغوية'],
        'certifications' => ['certifications', 'certificates', 'licenses', 'licenses and certifications', 'certifications and licenses', 'accreditations',
            'الشهادات', 'الشهادات المهنية', 'الرخص', 'الشهادات والدورات'],
        'projects' => ['projects', 'key projects', 'selected projects', 'personal projects', 'المشاريع', 'أبرز المشاريع'],
        'achievements' => ['achievements', 'key achievements', 'accomplishments', 'الإنجازات', 'أبرز الإنجازات'],
        'awards' => ['awards', 'honors', 'honours', 'awards and honors', 'الجوائز', 'الجوائز والتكريمات'],
        'volunteer' => ['volunteer', 'volunteering', 'volunteer experience', 'community service', 'العمل التطوعي', 'التطوع'],
        'publications' => ['publications', 'research', 'papers', 'المنشورات', 'الأبحاث'],
        'courses' => ['courses', 'training', 'trainings', 'professional development', 'workshops', 'الدورات', 'الدورات التدريبية', 'التدريب'],
        'references' => ['references', 'referees', 'المراجع', 'المعرفون'],
    ];

    public function match(string $line): ?SectionType
    {
        $normalised = $this->normalise($line);

        if ($normalised === '' || mb_strlen($normalised) > 45) {
            return null;
        }

        foreach (self::HEADINGS as $type => $headings) {
            if (in_array($normalised, $headings, true)) {
                return SectionType::from($type);
            }
        }

        return null;
    }

    /**
     * A short standalone line that looks like a heading we do not recognise.
     */
    public function looksLikeHeading(string $line): bool
    {
        $trimmed = trim($line, " \t:：");

        if ($trimmed === '' || mb_strlen($trimmed) > 35 || preg_match('/[\d@.,;]/u', $trimmed)) {
            return false;
        }

        $words = preg_split('/\s+/u', $trimmed) ?: [];

        return count($words) <= 4 && ($trimmed === mb_strtoupper($trimmed) && preg_match('/\p{Lu}/u', $trimmed));
    }

    private function normalise(string $line): string
    {
        $line = mb_strtolower(trim($line));
        $line = (string) preg_replace('/[:：\-–—•|]+$/u', '', $line);
        $line = str_replace(['&'], ['and'], $line);

        return trim((string) preg_replace('/\s+/u', ' ', $line));
    }
}
