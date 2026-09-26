<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class AiRequestFailed extends RuntimeException
{
    public static function unavailable(): self
    {
        return new self(__('ai.errors.unavailable'));
    }

    public static function refused(): self
    {
        return new self(__('ai.errors.refused'));
    }

    public static function failed(string $reason): self
    {
        return new self(__('ai.errors.failed'), previous: new RuntimeException($reason));
    }
}
