<?php

declare(strict_types=1);

namespace App\Services\JobMatching;

/**
 * Deterministic keyword extraction for English and Arabic job descriptions:
 * normalisation, stop-word removal and frequency-weighted uni/bigrams.
 */
final class KeywordExtractor
{
    private const MAX_KEYWORDS = 30;

    private const STOPWORDS_EN = ['a', 'about', 'above', 'across', 'after', 'all', 'also', 'an', 'and', 'any', 'are', 'as', 'at', 'be', 'because', 'been', 'being', 'best', 'both', 'but', 'by', 'can', 'candidate', 'candidates', 'company', 'could', 'day', 'days', 'do', 'does', 'each', 'either', 'etc', 'every', 'excellent', 'experience', 'for', 'from', 'further', 'good', 'great', 'has', 'have', 'having', 'help', 'high', 'highly', 'how', 'ideal', 'if', 'in', 'including', 'into', 'is', 'it', 'its', 'job', 'join', 'just', 'least', 'like', 'looking', 'make', 'may', 'more', 'most', 'must', 'new', 'not', 'of', 'on', 'one', 'or', 'other', 'our', 'out', 'over', 'plus', 'position', 'preferred', 'required', 'requirements', 'responsibilities', 'role', 'should', 'skills', 'so', 'some', 'strong', 'such', 'team', 'than', 'that', 'the', 'their', 'them', 'there', 'these', 'they', 'this', 'through', 'to', 'up', 'us', 'use', 'using', 'very', 'we', 'well', 'what', 'when', 'where', 'which', 'while', 'who', 'will', 'with', 'within', 'work', 'working', 'would', 'year', 'years', 'you', 'your', 'ability', 'able', 'knowledge', 'understanding', 'minimum', 'related', 'relevant', 'various', 'ensure', 'across', 'environment', 'opportunity', 'based', 'degree', 'equivalent'];

    private const STOPWORDS_AR = ['في', 'من', 'على', 'الى', 'عن', 'مع', 'او', 'و', 'ان', 'هذا', 'هذه', 'ذلك', 'التي', 'الذي', 'الذين', 'كان', 'كانت', 'يكون', 'تكون', 'قد', 'لا', 'ما', 'كل', 'بعض', 'غير', 'بين', 'حتي', 'عند', 'لدي', 'هو', 'هي', 'نحن', 'انت', 'كما', 'ايضا', 'اكثر', 'اقل', 'ذات', 'ذو', 'خلال', 'ضمن', 'حول', 'عبر', 'بما', 'فيها', 'فيه', 'لها', 'له', 'به', 'بها', 'لل', 'يجب', 'يفضل', 'مطلوب', 'المطلوب', 'الوظيفه', 'وظيفه', 'خبره', 'الخبره', 'سنوات', 'سنه', 'مهارات', 'المهارات', 'قدره', 'القدره', 'العمل', 'عمل', 'فريق', 'الفريق', 'الشركه', 'شركه', 'المرشح', 'جيد', 'ممتاز', 'قويه', 'قوي', 'الماده', 'مجال'];

    /**
     * @return list<string> Keywords in their normalised form, most important first.
     */
    public function extract(string $text): array
    {
        $tokens = $this->tokens($text);
        $scores = [];

        foreach ($tokens as $index => $token) {
            if ($this->isKeyword($token)) {
                $scores[$token] = ($scores[$token] ?? 0) + 1;
            }

            $next = $tokens[$index + 1] ?? null;

            if ($next !== null && $this->isKeyword($token) && $this->isKeyword($next)) {
                $bigram = $token.' '.$next;
                $scores[$bigram] = ($scores[$bigram] ?? 0) + 1.6;
            }
        }

        // A bigram seen once is noise; single words need at least one mention.
        $scores = array_filter($scores, fn (float|int $score, string $term): bool => ! str_contains($term, ' ') || $score >= 3.2, ARRAY_FILTER_USE_BOTH);
        arsort($scores);

        $selected = [];

        foreach (array_keys($scores) as $term) {
            // Skip words already covered by a stronger selected phrase.
            if (collect($selected)->contains(fn (string $chosen) => str_contains($chosen, ' ') && in_array($term, explode(' ', $chosen), true))) {
                continue;
            }

            $selected[] = $term;

            if (count($selected) >= self::MAX_KEYWORDS) {
                break;
            }
        }

        return $selected;
    }

    public function contains(string $haystack, string $keyword): bool
    {
        $normalised = ' '.implode(' ', $this->tokens($haystack)).' ';

        return str_contains($normalised, ' '.$keyword.' ');
    }

    /**
     * @return list<string>
     */
    public function tokens(string $text): array
    {
        $text = mb_strtolower($this->normaliseArabic($text));
        // Keep technical tokens such as c++, c#, node.js and .net intact.
        $text = (string) preg_replace('/[^\p{L}\p{N}+#.\s]/u', ' ', $text);
        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(array_map(function (string $token): string {
            $token = trim($token, '.');

            // Arabic definite article: "الإدارة" and "إدارة" are the same keyword.
            if (mb_strlen($token) > 4 && str_starts_with($token, 'ال') && preg_match('/^\p{Arabic}+$/u', $token)) {
                $token = mb_substr($token, 2);
            }

            return $token;
        }, $tokens), fn (string $token): bool => $token !== ''));
    }

    private function isKeyword(string $token): bool
    {
        if (is_numeric($token) || in_array($token, self::STOPWORDS_EN, true) || in_array($token, self::STOPWORDS_AR, true)) {
            return false;
        }

        return mb_strlen($token) >= 3 || preg_match('/[+#]/', $token) || in_array($token, ['ai', 'ml', 'ux', 'ui', 'qa', 'bi', 'hr', 'it', 'pr', 'go', 'r'], true);
    }

    private function normaliseArabic(string $text): string
    {
        $text = (string) preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text); // diacritics and tatweel

        return strtr($text, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ة' => 'ه', 'ى' => 'ي', 'ؤ' => 'و', 'ئ' => 'ي']);
    }
}
