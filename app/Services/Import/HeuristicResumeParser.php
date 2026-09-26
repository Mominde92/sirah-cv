<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Enums\SectionType;
use App\Services\Resume\SectionSchema;
use Illuminate\Support\Str;

/**
 * Rule-based CV parser. It fills a field only when the text shows clear evidence;
 * anything ambiguous is kept verbatim and listed in review_flags ("Needs review")
 * instead of being guessed.
 *
 * Output shape: ['language' => 'en'|'ar', 'personal' => [...], 'sections' => [...], 'review_flags' => [...]]
 */
final class HeuristicResumeParser
{
    private const MONTHS = 'jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|june?|july?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?'
        .'|يناير|فبراير|مارس|أبريل|ابريل|مايو|يونيو|يوليو|أغسطس|اغسطس|سبتمبر|أكتوبر|اكتوبر|نوفمبر|ديسمبر|كانون الثاني|شباط|آذار|نيسان|أيار|حزيران|تموز|آب|أيلول|تشرين الأول|تشرين الثاني|كانون الأول';

    private const PRESENT = 'present|current|now|today|ongoing|حتى الآن|حتى الان|الآن|حاليا|حالياً|الحاضر';

    private const MONTH_NUMBERS = [
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
        'يناير' => 1, 'فبراير' => 2, 'مارس' => 3, 'أبريل' => 4, 'ابريل' => 4, 'مايو' => 5, 'يونيو' => 6, 'يوليو' => 7, 'أغسطس' => 8, 'اغسطس' => 8,
        'سبتمبر' => 9, 'أكتوبر' => 10, 'اكتوبر' => 10, 'نوفمبر' => 11, 'ديسمبر' => 12,
        'كانون الثاني' => 1, 'شباط' => 2, 'آذار' => 3, 'نيسان' => 4, 'أيار' => 5, 'حزيران' => 6, 'تموز' => 7, 'آب' => 8, 'أيلول' => 9, 'تشرين الأول' => 10, 'تشرين الثاني' => 11, 'كانون الأول' => 12,
    ];

    /** @var list<array{field: string, reason: string, snippet: string}> */
    private array $flags = [];

    public function __construct(
        private readonly SectionHeadings $headings,
        private readonly SectionSchema $schema,
    ) {
    }

    /**
     * @return array{language: string, personal: array<string, string>, sections: list<array<string, mixed>>, review_flags: list<array<string, string>>}
     */
    public function parse(string $text): array
    {
        $this->flags = [];
        $lines = array_values(array_filter(array_map(fn ($line) => trim((string) $line), explode("\n", $text)), fn ($line) => $line !== ''));

        [$headerLines, $blocks] = $this->splitSections($lines);
        $personal = $this->personal($headerLines, $text);
        $sections = [];

        foreach ($blocks as $block) {
            $section = $this->section($block['type'], $block['title'], $block['lines']);

            if ($section !== null) {
                $sections[] = $section;
            }
        }

        return [
            'language' => $this->detectLanguage($text),
            'personal' => $personal,
            'sections' => $sections,
            'review_flags' => $this->flags,
        ];
    }

    /**
     * @param  list<string>  $lines
     * @return array{0: list<string>, 1: list<array{type: ?SectionType, title: string, lines: list<string>}>}
     */
    private function splitSections(array $lines): array
    {
        $header = [];
        $blocks = [];
        $current = null;

        foreach ($lines as $line) {
            $type = $this->headings->match($line);

            if ($type !== null || ($current !== null && $this->headings->looksLikeHeading($line))) {
                if ($current !== null) {
                    $blocks[] = $current;
                }

                $current = ['type' => $type, 'title' => trim($line, " :："), 'lines' => []];

                continue;
            }

            if ($current === null) {
                $header[] = $line;
            } else {
                $current['lines'][] = $line;
            }
        }

        if ($current !== null) {
            $blocks[] = $current;
        }

        return [$header, $blocks];
    }

    /**
     * @param  list<string>  $headerLines
     * @return array<string, string>
     */
    private function personal(array $headerLines, string $fullText): array
    {
        $personal = ['first_name' => '', 'last_name' => '', 'headline' => '', 'email' => '', 'phone' => '', 'location' => '', 'website' => '', 'linkedin' => '', 'github' => ''];

        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $fullText, $match)) {
            $personal['email'] = mb_strtolower($match[0]);
        }

        if (preg_match('/(?:\+|00)?\d[\d\s\-().]{7,}\d/u', implode("\n", $headerLines) ?: $fullText, $match)) {
            $personal['phone'] = trim((string) preg_replace('/\s+/', ' ', $match[0]));
        }

        if (preg_match('#(?:https?://)?(?:[a-z]{2,3}\.)?linkedin\.com/[^\s,|]+#i', $fullText, $match)) {
            $personal['linkedin'] = $this->schema->sanitizeUrl($match[0]);
        }

        if (preg_match('#(?:https?://)?(?:www\.)?github\.com/[^\s,|]+#i', $fullText, $match)) {
            $personal['github'] = $this->schema->sanitizeUrl($match[0]);
        }

        $remaining = [];

        foreach ($headerLines as $line) {
            $isContact = preg_match('/@|linkedin|github|https?:|www\./i', $line) || preg_match('/(?:\+|00)?\d[\d\s\-().]{7,}\d/u', $line);

            if (! $isContact) {
                $remaining[] = $line;
            } elseif ($personal['website'] === '' && preg_match('#(?:https?://|www\.)[^\s,|]+#i', $line, $match) && ! preg_match('/linkedin|github/i', $match[0])) {
                $personal['website'] = $this->schema->sanitizeUrl($match[0]);
            }
        }

        // Name: the first short line without digits, in the header area.
        $nameLine = null;

        foreach ($remaining as $index => $line) {
            $words = preg_split('/\s+/u', $line) ?: [];

            if (count($words) >= 2 && count($words) <= 5 && ! preg_match('/\d/', $line) && mb_strlen($line) <= 60) {
                $nameLine = $index;
                break;
            }
        }

        if ($nameLine !== null) {
            $parts = preg_split('/\s+/u', $remaining[$nameLine], 2) ?: [''];
            $personal['first_name'] = $this->schema->text($parts[0] ?? '', 80);
            $personal['last_name'] = $this->schema->text($parts[1] ?? '', 80);

            if ($nameLine !== 0) {
                $this->flag('personal.first_name', 'name_position', $remaining[$nameLine]);
            }

            $next = $remaining[$nameLine + 1] ?? null;

            if ($next !== null && mb_strlen($next) <= 80 && count(preg_split('/\s+/u', $next) ?: []) <= 10) {
                $personal['headline'] = $this->schema->text($next, 150);
            }

            foreach (array_slice($remaining, $nameLine + 2) as $line) {
                if ($personal['location'] === '' && mb_strlen($line) <= 60 && preg_match('/,|،/u', $line) && ! preg_match('/\d{3,}/', $line)) {
                    $personal['location'] = $this->schema->text($line, 120);
                } else {
                    $this->flag('personal', 'unassigned', $line);
                }
            }
        } else {
            $this->flag('personal.first_name', 'name_not_found', implode(' / ', array_slice($remaining, 0, 3)));
        }

        return $personal;
    }

    /**
     * @param  list<string>  $lines
     * @return array<string, mixed>|null
     */
    private function section(?SectionType $type, string $title, array $lines): ?array
    {
        if ($lines === []) {
            return null;
        }

        if ($type === null) {
            $this->flag('sections.custom', 'unknown_heading', $title);

            return ['type' => SectionType::Custom->value, 'title' => $title, 'items' => [[
                'id' => (string) Str::uuid(),
                'title' => '',
                'description' => $this->schema->text(implode("\n", $lines), 2000, multiline: true),
            ]]];
        }

        $items = match ($type) {
            SectionType::Summary => [['id' => (string) Str::uuid(), 'content' => $this->schema->text(implode(' ', $lines), 3000, multiline: true)]],
            SectionType::Skills => $this->skillItems($lines),
            SectionType::Languages => $this->languageItems($lines),
            SectionType::Experience, SectionType::Education, SectionType::Projects, SectionType::Volunteer => $this->datedEntries($type, $lines),
            default => $this->simpleEntries($type, $lines),
        };

        return ['type' => $type->value, 'title' => null, 'items' => $items];
    }

    /**
     * @param  list<string>  $lines
     * @return list<array<string, mixed>>
     */
    private function skillItems(array $lines): array
    {
        $names = [];

        foreach ($lines as $line) {
            $group = '';

            if (preg_match('/^([^:：]{2,40})[:：]\s*(.+)$/u', $line, $match)) {
                [$group, $line] = [trim($match[1]), $match[2]];
            }

            foreach (preg_split('/\s*[,،;|•·▪●]\s*|\s{3,}|\t/u', $line) ?: [] as $name) {
                $name = trim($name, " -*\t");

                if ($name !== '' && mb_strlen($name) <= 60) {
                    $names[] = ['id' => (string) Str::uuid(), 'name' => $name, 'level' => null, 'group' => $group];
                } elseif ($name !== '') {
                    $this->flag('sections.skills', 'long_skill', $name);
                }
            }
        }

        return array_slice($names, 0, SectionSchema::MAX_ITEMS);
    }

    /**
     * @param  list<string>  $lines
     * @return list<array<string, mixed>>
     */
    private function languageItems(array $lines): array
    {
        $levels = [
            'native' => '/native|mother tongue|first language|اللغة الأم|لغة أم|الأم/iu',
            'fluent' => '/fluent|excellent|proficient|بطلاقة|ممتاز|طلاقة/iu',
            'professional' => '/professional|advanced|very good|مهني|متقدم|جيد جدا/iu',
            'intermediate' => '/intermediate|good|conversational|متوسط|جيد/iu',
            'basic' => '/basic|beginner|elementary|مبتدئ|أساسي|ضعيف/iu',
        ];

        $items = [];

        foreach ($lines as $line) {
            foreach (preg_split('/\s*[,،;|•·]\s*/u', $line) ?: [] as $entry) {
                $entry = trim($entry, " -*\t");

                if ($entry === '') {
                    continue;
                }

                $proficiency = '';

                foreach ($levels as $level => $pattern) {
                    if (preg_match($pattern, $entry)) {
                        $proficiency = $level;
                        break;
                    }
                }

                $name = trim((string) preg_replace('/\s*[\(\-–—:：].*$/u', '', $entry));
                $items[] = ['id' => (string) Str::uuid(), 'name' => $this->schema->text($name !== '' ? $name : $entry, 80), 'proficiency' => $proficiency];
            }
        }

        return $items;
    }

    /**
     * Splits a section into entries at lines that contain a date range.
     *
     * @param  list<string>  $lines
     * @return list<array<string, mixed>>
     */
    private function datedEntries(SectionType $type, array $lines): array
    {
        $entries = [];
        $current = [];

        foreach ($lines as $line) {
            $isBullet = (bool) preg_match('/^[•\-*▪●·–]\s*/u', $line);

            if (! $isBullet && $this->dateRange($line) !== null && $current !== [] && $this->hasDate($current)) {
                // Title lines usually sit just above their dates: carry them into the new entry.
                $carried = [];

                while (count($carried) < 2 && $current !== []) {
                    $last = end($current);

                    if ($this->dateRange($last) !== null || preg_match('/^[•\-*▪●·–]/u', $last) || mb_strlen($last) > 100) {
                        break;
                    }

                    array_unshift($carried, array_pop($current));
                }

                $entries[] = $current;
                $current = $carried;
            }

            $current[] = $line;
        }

        if ($current !== []) {
            $entries[] = $current;
        }

        return array_values(array_map(fn (array $entry): array => $this->datedEntry($type, $entry), $entries));
    }

    /**
     * @param  list<string>  $lines
     * @return array<string, mixed>
     */
    private function datedEntry(SectionType $type, array $lines): array
    {
        $item = $this->schema->emptyItem($type);
        $headings = [];
        $bullets = [];
        $body = [];

        foreach ($lines as $line) {
            $range = $this->dateRange($line);

            if ($range !== null && ($item['start_date'] ?? '') === '') {
                $item['start_date'] = $range['start'];
                $item['end_date'] = $range['end'];

                if (array_key_exists('is_current', $item)) {
                    $item['is_current'] = $range['current'];
                }

                if ($range['approximate']) {
                    $this->flag("sections.{$type->value}", 'month_assumed', $range['text']);
                }

                $rest = trim((string) preg_replace('/\s*[|,،·–—\-]\s*$/u', '', str_replace($range['text'], '', $line)), " |,،·-–—\t");

                if ($rest !== '') {
                    $headings[] = $rest;
                }

                continue;
            }

            if (preg_match('/^[•\-*▪●·–]\s*(.+)$/u', $line, $match)) {
                $bullets[] = $match[1];
            } elseif (count($headings) < 2 && $body === [] && mb_strlen($line) <= 100) {
                $headings[] = $line;
            } else {
                $body[] = $line;
            }
        }

        [$primary, $secondary] = match ($type) {
            SectionType::Experience => ['position', 'employer'],
            SectionType::Education => ['degree', 'institution'],
            SectionType::Projects => ['name', 'role'],
            default => ['role', 'organization'],
        };

        // "Title at Company" / "Title — Company" on one line.
        if (count($headings) === 1 && preg_match('/^(.+?)\s+(?:at|@|—|–|-|\||لدى|في)\s+(.+)$/u', $headings[0], $match)) {
            $headings = [trim($match[1]), trim($match[2])];
        }

        $item[$primary] = $this->schema->text($headings[0] ?? '', 150);
        $item[$secondary] = $this->schema->text($headings[1] ?? '', 150);

        foreach (array_slice($headings, 2) as $extra) {
            $body[] = $extra;
        }

        if (($item['start_date'] ?? '') === '') {
            $this->flag("sections.{$type->value}", 'missing_dates', $lines[0]);
        }

        // Which line is the title and which the organisation is a convention, not a fact.
        if ($item[$primary] !== '' || $item[$secondary] !== '') {
            $this->flag("sections.{$type->value}", 'check_order', trim($item[$primary].' / '.$item[$secondary], ' /'));
        }

        if (array_key_exists('highlights', $item)) {
            $item['highlights'] = array_values(array_filter(array_map(fn ($b) => $this->schema->text($b, 400), $bullets)));
        } else {
            $body = array_merge($body, $bullets);
        }

        $item['description'] = $this->schema->text(implode("\n", $body), 3000, multiline: true);

        return $item;
    }

    /**
     * @param  list<string>  $lines
     * @return list<array<string, mixed>>
     */
    private function simpleEntries(SectionType $type, array $lines): array
    {
        $items = [];
        $nameField = $this->schema->labelField($type);

        foreach ($lines as $line) {
            $line = (string) preg_replace('/^[•\-*▪●·–]\s*/u', '', $line);
            $item = $this->schema->emptyItem($type);
            $dateField = collect(['date', 'issued_at'])->first(fn ($field) => array_key_exists($field, $item));

            if ($dateField !== null && preg_match('/\b((?:19|20)\d{2})\b/', $line, $match)) {
                $item[$dateField] = $match[1].'-01';
                $line = trim((string) preg_replace('/\s*[\(\[]?\b'.$match[1].'\b[\)\]]?\s*/', ' ', $line), " ,،-–—|");
                $this->flag("sections.{$type->value}", 'month_assumed', $line);
            }

            $item[$nameField] = $this->schema->text($line, 150);
            $items[] = $item;
        }

        return array_slice($items, 0, SectionSchema::MAX_ITEMS);
    }

    /**
     * @return array{start: string, end: string, current: bool, text: string, approximate: bool}|null
     */
    private function dateRange(string $line): ?array
    {
        $date = '(?:(?:'.self::MONTHS.')\.?\s*,?\s*(?:19|20)\d{2}|(?:0?[1-9]|1[0-2])\s*[\/\-.]\s*(?:19|20)\d{2}|(?:19|20)\d{2})';
        $pattern = '/('.$date.')\s*(?:-|–|—|to|until|till|إلى|الى|حتى|~)\s*('.$date.'|'.self::PRESENT.')/iu';

        if (! preg_match($pattern, $line, $match)) {
            return null;
        }

        $current = (bool) preg_match('/^(?:'.self::PRESENT.')$/iu', trim($match[2]));

        return [
            'start' => $this->toMonth($match[1]),
            'end' => $current ? '' : $this->toMonth($match[2]),
            'current' => $current,
            'text' => $match[0],
            // Year-only dates become January of that year; the reviewer is told.
            'approximate' => (bool) preg_match('/^(?:19|20)\d{2}$/', trim($match[1])) || (! $current && preg_match('/^(?:19|20)\d{2}$/', trim($match[2]))),
        ];
    }

    /**
     * @param  list<string>  $lines
     */
    private function hasDate(array $lines): bool
    {
        foreach ($lines as $line) {
            if ($this->dateRange($line) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * "Jan 2021" → "2021-01"; a bare year becomes January of that year (flagged by the caller).
     */
    private function toMonth(string $value): string
    {
        $value = mb_strtolower(trim($value));

        if (preg_match('/^(0?[1-9]|1[0-2])\s*[\/\-.]\s*((?:19|20)\d{2})$/', $value, $m)) {
            return sprintf('%s-%02d', $m[2], (int) $m[1]);
        }

        preg_match('/((?:19|20)\d{2})/', $value, $year);

        foreach (self::MONTH_NUMBERS as $name => $number) {
            if (str_starts_with($value, $name)) {
                return sprintf('%s-%02d', $year[1] ?? '', $number);
            }
        }

        return isset($year[1]) ? $year[1].'-01' : '';
    }

    private function detectLanguage(string $text): string
    {
        $arabic = preg_match_all('/\p{Arabic}/u', $text);
        $letters = max(1, preg_match_all('/\p{L}/u', $text));

        return $arabic / $letters > 0.3 ? 'ar' : 'en';
    }

    private function flag(string $field, string $reason, string $snippet): void
    {
        $this->flags[] = ['field' => $field, 'reason' => $reason, 'snippet' => mb_substr($snippet, 0, 300)];
    }
}
