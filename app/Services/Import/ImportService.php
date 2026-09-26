<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Enums\Feature;
use App\Enums\ProcessingStatus;
use App\Enums\ResumeSource;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\FeatureLimitReached;
use App\Jobs\ProcessResumeImport;
use App\Models\Resume;
use App\Models\ResumeImport;
use App\Models\ResumeTemplate;
use App\Models\User;
use App\Services\Import\Extractors\DocxTextExtractor;
use App\Services\Import\Extractors\PdfTextExtractor;
use App\Services\Resume\ResumeService;
use App\Services\Usage\FeatureGate;
use App\Services\Usage\UsageMeter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Import of an existing CV. The original file and the full extracted text are kept;
 * nothing becomes a resume until the user has reviewed and confirmed the result.
 */
final class ImportService
{
    public const ABANDONED_AFTER_DAYS = 7;

    public function __construct(
        private readonly FeatureGate $gate,
        private readonly UsageMeter $meter,
        private readonly PdfTextExtractor $pdf,
        private readonly DocxTextExtractor $docx,
        private readonly HeuristicResumeParser $heuristic,
        private readonly AiResumeParser $aiParser,
        private readonly ResumeService $resumes,
    ) {
    }

    /**
     * @throws FeatureLimitReached
     */
    public function store(User $user, UploadedFile $file): ResumeImport
    {
        $this->gate->authorize($user, Feature::CvImport);

        $extension = $file->getMimeType() === 'application/pdf' ? 'pdf' : 'docx';
        $disk = (string) config('cv.disks.private', 'local');
        $path = $file->storeAs('imports/'.$user->id, Str::uuid().'.'.$extension, $disk);

        $import = $user->imports()->create([
            'status' => ProcessingStatus::Pending,
            'disk' => $disk,
            'path' => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'mime_type' => (string) $file->getMimeType(),
            'file_size' => (int) $file->getSize(),
        ]);

        ProcessResumeImport::dispatch($import->id)->afterCommit();

        return $import;
    }

    public function process(ResumeImport $import): void
    {
        $import->forceFill(['status' => ProcessingStatus::Processing])->save();
        $local = Storage::disk($import->disk)->path($import->path);

        try {
            $text = $import->mime_type === 'application/pdf' ? $this->pdf->extract($local) : $this->docx->extract($local);
        } catch (Throwable $exception) {
            $this->fail($import, __('imports.errors.unreadable'), $exception);

            return;
        }

        if (mb_strlen(trim($text)) < 40) {
            $this->fail($import, __('imports.errors.no_text'));

            return;
        }

        [$parsed, $parser] = $this->parse($import->user, $text);

        $import->forceFill([
            'extracted_text' => $text,
            'parsed_data' => array_diff_key($parsed, ['review_flags' => true]),
            'review_flags' => $parsed['review_flags'],
            'parser' => $parser,
            'status' => ProcessingStatus::NeedsReview,
            'processed_at' => now(),
        ])->save();

        $this->meter->increment($import->user, Feature::CvImport);
    }

    /**
     * Creates the resume from the reviewed data.
     *
     * @param  array<string, mixed>  $personal
     * @param  list<array<string, mixed>>  $sections
     */
    public function confirm(ResumeImport $import, User $user, string $title, ResumeTemplate $template, string $language, array $personal, array $sections): Resume
    {
        return DB::transaction(function () use ($import, $user, $title, $template, $language, $personal, $sections): Resume {
            $resume = $this->resumes->create($user, $title, $template, $language, ResumeSource::Import, $personal, $sections);

            $import->forceFill(['resume_id' => $resume->id, 'status' => ProcessingStatus::Completed])->save();

            return $resume;
        });
    }

    public function delete(ResumeImport $import): void
    {
        Storage::disk($import->disk)->delete($import->path);
        $import->delete();
    }

    public function fail(ResumeImport $import, string $message, ?Throwable $exception = null): void
    {
        $import->forceFill(['status' => ProcessingStatus::Failed, 'error' => $message, 'processed_at' => now()])->save();

        if ($exception !== null) {
            report($exception);
        }
    }

    /**
     * Unconfirmed imports older than a week are removed together with their file.
     */
    public function pruneAbandoned(): int
    {
        $count = 0;

        ResumeImport::query()
            ->whereNull('resume_id')
            ->where('created_at', '<', now()->subDays(self::ABANDONED_AFTER_DAYS))
            ->chunkById(200, function ($imports) use (&$count): void {
                foreach ($imports as $import) {
                    $this->delete($import);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * AI extraction when available and allowed; the rule-based parser otherwise
     * (and as a fallback whenever the AI call fails).
     *
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function parse(User $user, string $text): array
    {
        if ($this->aiParser->available() && $this->gate->allows($user, Feature::AiRequests)) {
            try {
                return [$this->aiParser->parse($user, $text), 'ai'];
            } catch (AiRequestFailed|FeatureLimitReached $exception) {
                report($exception);
            }
        }

        return [$this->heuristic->parse($text), 'heuristic'];
    }
}
