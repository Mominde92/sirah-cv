<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ExportFormat;
use App\Enums\ProcessingStatus;
use App\Models\Resume;
use App\Models\ResumeExport;
use App\Services\Export\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportController extends Controller
{
    public function __construct(private readonly ExportService $exports)
    {
    }

    public function store(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('export', $resume);

        $validated = $request->validate([
            'format' => ['required', Rule::enum(ExportFormat::class)],
        ]);

        $export = $this->exports->request($request->user(), $resume, ExportFormat::from($validated['format']));

        return new JsonResponse($this->payload($export), 202);
    }

    public function show(ResumeExport $export): JsonResponse
    {
        $this->authorize('view', $export);

        return new JsonResponse($this->payload($export));
    }

    public function download(Request $request, ResumeExport $export): StreamedResponse
    {
        // The signature (checked by middleware) authorises the link; a signed-in user
        // must additionally be the owner, so links cannot be reused across accounts.
        if ($request->user() !== null) {
            $this->authorize('download', $export);
        }

        abort_unless($export->isDownloadable() && Storage::disk($export->disk)->exists((string) $export->path), 410, __('exports.errors.expired'));

        $export->forceFill(['downloaded_at' => now()])->save();

        return Storage::disk($export->disk)->download((string) $export->path, $this->exports->downloadName($export), [
            'Content-Type' => $export->format->mimeType(),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ResumeExport $export): array
    {
        return [
            'id' => $export->uuid,
            'format' => $export->format->value,
            'status' => $export->status->value,
            'error' => $export->status === ProcessingStatus::Failed ? ($export->error ?: __('exports.errors.failed')) : null,
            'status_url' => route('exports.show', $export),
            'download_url' => $export->isDownloadable() ? $this->exports->temporaryDownloadUrl($export) : null,
        ];
    }
}
