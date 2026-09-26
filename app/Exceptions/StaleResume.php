<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * The resume was saved elsewhere (another tab or device) after the editor loaded it.
 */
final class StaleResume extends RuntimeException
{
    public function __construct(public readonly int $currentVersion)
    {
        parent::__construct('The resume was modified by another session.');
    }
}
