<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle of queued file work: exports, imports, design analyses, job matches.
 */
enum ProcessingStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case NeedsReview = 'needs_review';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isFinished(): bool
    {
        return in_array($this, [self::NeedsReview, self::Completed, self::Failed], true);
    }
}
