<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Qualitative ATS compatibility of a template. Deliberately not a percentage:
 * parser behaviour differs between vendors and cannot be measured honestly as one number.
 */
enum AtsStatus: string
{
    case AtsSafe = 'ats_safe';
    case AtsOptimized = 'ats_optimized';
    case Creative = 'creative';

    public function label(): string
    {
        return __('enums.ats_status.'.$this->value.'.label');
    }

    public function description(): string
    {
        return __('enums.ats_status.'.$this->value.'.description');
    }

    public function isAtsFriendly(): bool
    {
        return $this !== self::Creative;
    }
}
