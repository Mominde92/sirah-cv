<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Feature;
use App\Http\Requests\Resume\RenameResumeRequest;
use App\Http\Requests\Resume\StoreResumeRequest;
use App\Models\Resume;
use App\Models\ResumeTemplate;
use App\Services\Localization\LocaleService;
use App\Services\Resume\ResumeService;
use App\Services\Templates\TemplateRegistry;
use App\Services\Usage\FeatureGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ResumeController extends Controller
{
    public function __construct(private readonly ResumeService $resumes)
    {
    }

    public function index(Request $request, FeatureGate $gate): View
    {
        $resumes = $request->user()->resumes()
            ->with('template:id,slug,name,ats_status,is_premium')
            ->select(['id', 'uuid', 'user_id', 'template_id', 'title', 'language', 'status', 'updated_at', 'last_edited_at'])
            ->recentlyEdited()
            ->paginate(12);

        return view('resumes.index', [
            'resumes' => $resumes,
            'allowance' => $gate->allowance($request->user(), Feature::Resumes),
        ]);
    }

    public function create(Request $request, TemplateRegistry $templates, LocaleService $locales, FeatureGate $gate): View
    {
        $this->authorize('create', Resume::class);

        $selected = $templates->findActiveBySlug((string) $request->query('template')) ?? $templates->default();

        return view('resumes.create', [
            'templates' => $templates->active(),
            'selected' => $selected,
            'locales' => $locales->all(),
            'language' => $request->user()->locale,
            'canUsePremium' => $gate->allows($request->user(), Feature::PremiumTemplates),
            'allowance' => $gate->allowance($request->user(), Feature::Resumes),
        ]);
    }

    public function store(StoreResumeRequest $request): RedirectResponse
    {
        $resume = $this->resumes->create(
            user: $request->user(),
            title: $request->validated('title'),
            template: ResumeTemplate::findOrFail($request->integer('template_id')),
            language: $request->validated('language'),
        );

        return redirect()->route('resumes.edit', $resume)->with('status', __('resumes.created'));
    }

    public function update(RenameResumeRequest $request, Resume $resume): RedirectResponse|JsonResponse
    {
        $this->resumes->rename($resume, $request->validated('title'));

        return $request->expectsJson()
            ? new JsonResponse(['title' => $resume->title])
            : back()->with('status', __('resumes.renamed'));
    }

    public function duplicate(Request $request, Resume $resume): RedirectResponse
    {
        $this->authorize('duplicate', $resume);

        $copy = $this->resumes->duplicate($resume, $request->user());

        return redirect()->route('resumes.index')->with('status', __('resumes.duplicated', ['title' => $copy->title]));
    }

    public function destroy(Resume $resume): RedirectResponse
    {
        $this->authorize('delete', $resume);

        $this->resumes->delete($resume);

        return redirect()->route('resumes.index')->with('status', __('resumes.deleted'));
    }
}
