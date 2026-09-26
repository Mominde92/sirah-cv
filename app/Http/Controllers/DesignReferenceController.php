<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Feature;
use App\Enums\ProcessingStatus;
use App\Http\Requests\Design\StoreDesignRequest;
use App\Models\DesignReference;
use App\Models\Resume;
use App\Services\Design\DesignReferenceService;
use App\Services\Localization\LocaleService;
use App\Services\Templates\ResumeDocumentFactory;
use App\Services\Templates\SampleResume;
use App\Services\Templates\TemplateRegistry;
use App\Services\Templates\TemplateRenderer;
use App\Services\Usage\FeatureGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DesignReferenceController extends Controller
{
    public function __construct(private readonly DesignReferenceService $designs)
    {
    }

    public function create(Request $request, FeatureGate $gate): View
    {
        return view('designs.create', [
            'allowance' => $gate->allowance($request->user(), Feature::DesignReference),
            'recent' => $request->user()->designReferences()->latest()->limit(5)->get(['id', 'uuid', 'original_name', 'status', 'created_at']),
        ]);
    }

    public function store(StoreDesignRequest $request): RedirectResponse
    {
        $design = $this->designs->store($request->user(), $request->file('file'));

        return redirect()->route('designs.show', $design);
    }

    public function show(Request $request, DesignReference $design, LocaleService $locales): View
    {
        $this->authorize('view', $design);

        return view('designs.show', [
            'design' => $design->load('suggestedTemplate'),
            'processing' => in_array($design->status, [ProcessingStatus::Pending, ProcessingStatus::Processing], true),
            'resumes' => $request->user()->resumes()->recentlyEdited()->get(['id', 'uuid', 'title']),
            'locales' => $locales->all(),
        ]);
    }

    /**
     * The suggested template rendered with the inspired styling on sample content.
     */
    public function preview(Request $request, DesignReference $design, ResumeDocumentFactory $documents, TemplateRenderer $renderer, SampleResume $samples, TemplateRegistry $templates, LocaleService $locales): Response
    {
        $this->authorize('view', $design);

        $template = $design->suggestedTemplate ?? $templates->default();
        abort_if($template === null, 404);

        $language = $locales->isSupported($request->query('lang')) ? (string) $request->query('lang') : app()->getLocale();
        $sample = $samples->data($language);
        $html = $renderer->render($documents->make($template, $language, $sample['personal'], $sample['sections'], $design->generated_styling), TemplateRenderer::MODE_THUMBNAIL);

        return response($html)
            ->header('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; font-src 'self'; img-src 'self' data:; frame-ancestors 'self'")
            ->header('Cache-Control', 'private, max-age=600');
    }

    /**
     * The uploaded reference, for its owner only (never public).
     */
    public function file(DesignReference $design): StreamedResponse
    {
        $this->authorize('view', $design);

        abort_unless(Storage::disk($design->disk)->exists($design->path), 404);

        return Storage::disk($design->disk)->response($design->path, null, [
            'Content-Type' => $design->mime_type,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'",
        ]);
    }

    public function apply(Request $request, DesignReference $design, LocaleService $locales): RedirectResponse
    {
        $this->authorize('update', $design);
        abort_unless($design->status === ProcessingStatus::Completed, 409);

        $validated = $request->validate([
            'target' => ['required', Rule::in(['new', 'existing'])],
            'resume' => ['required_if:target,existing', 'nullable', 'string'],
            'language' => ['required_if:target,new', 'nullable', Rule::in($locales->codes())],
        ]);

        if ($validated['target'] === 'existing') {
            $resume = Resume::query()->where('uuid', $validated['resume'])->firstOrFail();
            $this->authorize('update', $resume);
            $resume = $this->designs->applyTo($design, $request->user(), $resume);
        } else {
            $resume = $this->designs->createResume($design, $request->user(), (string) $validated['language']);
        }

        return redirect()->route('resumes.edit', $resume)->with('status', __('designs.applied'));
    }

    public function destroy(DesignReference $design): RedirectResponse
    {
        $this->authorize('delete', $design);

        $this->designs->delete($design);

        return redirect()->route('designs.create')->with('status', __('designs.deleted'));
    }
}
