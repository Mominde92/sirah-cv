<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Models\ResumeTemplate;
use App\Services\Localization\LocaleService;
use App\Services\Templates\ResumeDocumentFactory;
use App\Services\Templates\SampleResume;
use App\Services\Templates\TemplateRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Standalone HTML renders for iframes (thumbnails, template gallery).
 * Served with a strict CSP: resume documents never need scripts.
 */
final class PreviewController extends Controller
{
    public function __construct(
        private readonly ResumeDocumentFactory $documents,
        private readonly TemplateRenderer $renderer,
    ) {
    }

    public function resume(Request $request, Resume $resume): Response
    {
        $this->authorize('view', $resume);

        return $this->html($this->renderer->render($this->documents->fromResume($resume), $this->mode($request)), privateCache: true);
    }

    public function template(Request $request, ResumeTemplate $template, SampleResume $samples, LocaleService $locales): Response
    {
        abort_unless($template->is_active, 404);

        $language = $locales->isSupported($request->query('lang')) ? (string) $request->query('lang') : app()->getLocale();
        $sample = $samples->data($language);
        $document = $this->documents->make($template, $language, $sample['personal'], $sample['sections']);

        return $this->html($this->renderer->render($document, $this->mode($request)), privateCache: false);
    }

    /**
     * The resume photo, streamed from the private disk to its owner only.
     */
    public function photo(Resume $resume): StreamedResponse
    {
        $this->authorize('view', $resume);

        $path = $resume->data['personal']['photo_path'] ?? '';
        $disk = Storage::disk(config('cv.disks.private', 'local'));

        abort_unless(is_string($path) && $path !== '' && $disk->exists($path), 404);

        return $disk->response($path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function mode(Request $request): string
    {
        return $request->boolean('thumb') ? TemplateRenderer::MODE_THUMBNAIL : TemplateRenderer::MODE_PREVIEW;
    }

    private function html(string $html, bool $privateCache): Response
    {
        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; font-src 'self'; img-src 'self' data:; frame-ancestors 'self'; base-uri 'none'; form-action 'none'")
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Cache-Control', $privateCache ? 'private, no-store' : 'public, max-age=3600');
    }
}
