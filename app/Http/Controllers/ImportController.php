<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Feature;
use App\Enums\ProcessingStatus;
use App\Http\Requests\Import\ConfirmImportRequest;
use App\Http\Requests\Import\StoreImportRequest;
use App\Models\ResumeImport;
use App\Models\ResumeTemplate;
use App\Services\Import\ImportService;
use App\Services\Localization\LocaleService;
use App\Services\Resume\SectionSchema;
use App\Services\Templates\TemplateRegistry;
use App\Services\Usage\FeatureGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ImportController extends Controller
{
    public function __construct(private readonly ImportService $imports)
    {
    }

    public function create(Request $request, FeatureGate $gate): View
    {
        return view('imports.create', [
            'allowance' => $gate->allowance($request->user(), Feature::CvImport),
            'recent' => $request->user()->imports()->latest()->limit(5)->get(['id', 'uuid', 'original_name', 'status', 'resume_id', 'created_at']),
        ]);
    }

    public function store(StoreImportRequest $request): RedirectResponse
    {
        $import = $this->imports->store($request->user(), $request->file('file'));

        return redirect()->route('imports.show', $import);
    }

    public function show(Request $request, ResumeImport $import, TemplateRegistry $templates, LocaleService $locales, FeatureGate $gate, SectionSchema $schema): View
    {
        $this->authorize('view', $import);

        return view('imports.show', [
            'import' => $import,
            'processing' => in_array($import->status, [ProcessingStatus::Pending, ProcessingStatus::Processing], true),
            'templates' => $templates->active()->filter(fn (ResumeTemplate $template) => $templates->isAvailableTo($request->user(), $template))->values(),
            'locales' => $locales->all(),
            'schema' => $schema,
            'resumesAllowance' => $gate->allowance($request->user(), Feature::Resumes),
        ]);
    }

    public function confirm(ConfirmImportRequest $request, ResumeImport $import): RedirectResponse
    {
        abort_unless($import->status === ProcessingStatus::NeedsReview, 409);

        $sections = collect($request->input('sections', []))
            ->filter(fn (array $section): bool => (bool) ($section['include'] ?? true))
            ->map(fn (array $section): array => [
                'type' => (string) $section['type'],
                'title' => $section['title'] ?? null,
                'items' => array_values((array) ($section['items'] ?? [])),
            ])
            ->values()
            ->all();

        $resume = $this->imports->confirm(
            import: $import,
            user: $request->user(),
            title: $request->validated('title'),
            template: ResumeTemplate::findOrFail($request->integer('template_id')),
            language: $request->validated('language'),
            personal: (array) $request->input('personal', []),
            sections: $sections,
        );

        return redirect()->route('resumes.edit', $resume)->with('status', __('imports.created'));
    }

    public function destroy(ResumeImport $import): RedirectResponse
    {
        $this->authorize('delete', $import);

        $this->imports->delete($import);

        return redirect()->route('imports.create')->with('status', __('imports.deleted'));
    }
}
