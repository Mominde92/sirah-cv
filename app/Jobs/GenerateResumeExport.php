<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ProcessingStatus;
use App\Models\ResumeExport;
use App\Services\Export\ExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class GenerateResumeExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public readonly int $exportId)
    {
        $this->onQueue('exports');
    }

    public function handle(ExportService $exports): void
    {
        $export = ResumeExport::query()->with('resume', 'user')->find($this->exportId);

        if ($export === null || $export->status === ProcessingStatus::Completed) {
            return;
        }

        $exports->generate($export);
    }

    public function failed(?Throwable $exception): void
    {
        $export = ResumeExport::query()->find($this->exportId);

        if ($export !== null) {
            app(ExportService::class)->fail($export, __('exports.errors.failed'), $exception);
        }
    }
}
