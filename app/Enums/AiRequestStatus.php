<?php

declare(strict_types=1);

namespace App\Enums;

enum AiRequestStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
