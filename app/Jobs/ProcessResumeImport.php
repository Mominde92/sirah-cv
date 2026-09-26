<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ProcessingStatus;
use App\Models\ResumeImport;
use App\Services\Import\ImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class ProcessResumeImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(public readonly int $importId)
    {
        $this->onQueue('imports');
    }

    public function handle(ImportService $imports): void
    {
        $import = ResumeImport::query()->with('user')->find($this->importId);

        if ($import === null || $import->status !== ProcessingStatus::Pending) {
            return;
        }

        $imports->process($import);
    }

    public function failed(?Throwable $exception): void
    {
        $import = ResumeImport::query()->find($this->importId);

        if ($import !== null) {
            app(ImportService::class)->fail($import, __('imports.errors.unreadable'), $exception);
        }
    }
}
