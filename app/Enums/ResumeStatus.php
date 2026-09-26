<?php

declare(strict_types=1);

namespace App\Enums;

enum ResumeStatus: string
{
    case Draft = 'draft';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return __('enums.resume_status.'.$this->value);
    }
}
