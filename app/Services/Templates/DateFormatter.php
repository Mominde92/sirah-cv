<?php

declare(strict_types=1);

namespace App\Services\Templates;

use Carbon\CarbonImmutable;

/**
 * Formats "YYYY-MM" values in the resume's language (e.g. "Jan 2021", "يناير 2021").
 */
final class DateFormatter
{
    public function month(?string $value, string $locale): string
    {
        if ($value === null || ! preg_match('/^(\d{4})-(\d{2})$/', $value, $m)) {
            return (string) $value;
        }

        return CarbonImmutable::create((int) $m[1], (int) $m[2], 1)->locale($locale)->translatedFormat($locale === 'ar' ? 'F Y' : 'M Y');
    }

    public function range(?string $start, ?string $end, bool $current, string $locale): string
    {
        $from = $this->month($start, $locale);
        $to = $current ? __('resume.present', locale: $locale) : $this->month($end, $locale);

        return match (true) {
            $from !== '' && $to !== '' => $from.' – '.$to,
            default => $from !== '' ? $from : $to,
        };
    }
}
