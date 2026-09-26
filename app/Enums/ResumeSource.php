<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a resume came into existence.
 */
enum ResumeSource: string
{
    case Scratch = 'scratch';
    case Duplicate = 'duplicate';
    case Import = 'import';
    case DesignReference = 'design_reference';
}
