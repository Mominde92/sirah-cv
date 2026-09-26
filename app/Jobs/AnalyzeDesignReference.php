<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ProcessingStatus;
use App\Models\DesignReference;
use App\Services\Design\DesignReferenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class AnalyzeDesignReference implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public readonly int $designId)
    {
        $this->onQueue('imports');
    }

    public function handle(DesignReferenceService $designs): void
    {
        $design = DesignReference::query()->with('user')->find($this->designId);

        if ($design === null || $design->status !== ProcessingStatus::Pending) {
            return;
        }

        $designs->analyze($design);
    }

    public function failed(?Throwable $exception): void
    {
        $design = DesignReference::query()->find($this->designId);

        if ($design !== null) {
            app(DesignReferenceService::class)->fail($design, __('designs.errors.unreadable'), $exception);
        }
    }
}
