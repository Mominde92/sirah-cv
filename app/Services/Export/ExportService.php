<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Contracts\PdfRenderer;
use App\Enums\ExportFormat;
use App\Enums\ProcessingStatus;
use App\Exceptions\FeatureLimitReached;
use App\Jobs\GenerateResumeExport;
use App\Models\Resume;
use App\Models\ResumeExport;
use App\Models\User;
use App\Services\Export\Docx\DocxBuilder;
use App\Services\Templates\ResumeDocumentFactory;
use App\Services\Usage\FeatureGate;
use App\Services\Usage\UsageMeter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

/**
 * PDF / DOCX exports. The HTTP request only records the export and queues it;
 * rendering happens in GenerateResumeExport so large documents never block a request.
 */
final class ExportService
{
    public function __construct(
        private readonly FeatureGate $gate,
        private readonly UsageMeter $meter,
        private readonly ResumeDocumentFactory $documents,
        private readonly PdfRenderer $pdf,
        private readonly DocxBuilder $docx,
    ) {
    }

    /**
     * @throws FeatureLimitReached
     */
    public function request(User $user, Resume $resume, ExportFormat $format): ResumeExport
    {
        $this->gate->authorize($user, $format->feature());

        $export = $user->exports()->create([
            'resume_id' => $resume->id,
            'format' => $format,
            'status' => ProcessingStatus::Pending,
            'resume_version' => $resume->version,
            'disk' => config('cv.disks.private', 'local'),
        ]);

        GenerateResumeExport::dispatch($export->id)->afterCommit();

        return $export->refresh();
    }

    /**
     * Runs inside the queued job.
     */
    public function generate(ResumeExport $export): void
    {
        $resume = $export->resume;

        if ($resume === null || $resume->trashed()) {
            $this->fail($export, __('exports.errors.resume_missing'));

            return;
        }

        $export->forceFill(['status' => ProcessingStatus::Processing, 'started_at' => now()])->save();

        $document = $this->documents->fromResume($resume);
        $bytes = $export->format === ExportFormat::Pdf ? $this->pdf->render($document) : $this->docx->build($document);

        $path = 'exports/'.$export->user_id.'/'.$export->uuid.'.'.$export->format->extension();
        Storage::disk($export->disk)->put($path, $bytes);

        $export->forceFill([
            'status' => ProcessingStatus::Completed,
            'path' => $path,
            'file_size' => strlen($bytes),
            'options' => ['renderer' => $export->format === ExportFormat::Pdf ? $this->pdf->name() : 'phpword', 'template' => $document->templateSlug],
            'completed_at' => now(),
            'expires_at' => now()->addHours((int) config('cv.exports.retention_hours', 24)),
        ])->save();

        // Counted only once a file was actually produced.
        $this->meter->increment($export->user, $export->format->feature());
    }

    public function fail(ResumeExport $export, string $message, ?Throwable $exception = null): void
    {
        $export->forceFill([
            'status' => ProcessingStatus::Failed,
            'error' => $message,
            'completed_at' => now(),
        ])->save();

        if ($exception !== null) {
            report($exception);
        }
    }

    public function temporaryDownloadUrl(ResumeExport $export): string
    {
        return URL::temporarySignedRoute(
            'exports.download',
            now()->addMinutes((int) config('cv.exports.download_url_ttl_minutes', 15)),
            ['export' => $export],
        );
    }

    public function downloadName(ResumeExport $export): string
    {
        $resume = $export->resume;
        $base = $resume !== null ? Str::slug(($resume->data['personal']['first_name'] ?? '').' '.($resume->data['personal']['last_name'] ?? '').' '.$resume->title) : '';

        return ($base !== '' ? $base : 'resume').'.'.$export->format->extension();
    }

    /**
     * Deletes files past their retention window. Rows are kept for download statistics.
     */
    public function pruneExpired(): int
    {
        $count = 0;

        ResumeExport::query()->expired()->chunkById(200, function ($exports) use (&$count): void {
            foreach ($exports as $export) {
                Storage::disk($export->disk)->delete((string) $export->path);
                $export->forceFill(['path' => null])->save();
                $count++;
            }
        });

        return $count;
    }
}
