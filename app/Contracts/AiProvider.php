<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\AiRequest;
use App\Data\AiResponse;
use App\Exceptions\AiRequestFailed;

/**
 * One text-generation backend. Business logic depends only on this contract,
 * so providers can be swapped from configuration.
 */
interface AiProvider
{
    /**
     * @throws AiRequestFailed
     */
    public function complete(AiRequest $request): AiResponse;

    public function name(): string;

    public function model(): string;

    public function supportsVision(): bool;
}
