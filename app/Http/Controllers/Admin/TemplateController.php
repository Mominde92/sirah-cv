<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TemplateRequest;
use App\Models\ResumeTemplate;
use App\Services\Admin\AdminAudit;
use App\Services\Templates\TemplateRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TemplateController extends Controller
{
    public function __construct(
        private readonly TemplateRegistry $registry,
        private readonly AdminAudit $audit,
    ) {
    }

    public function index(): View
    {
        return view('admin.templates.index', [
            'templates' => ResumeTemplate::query()->withCount('resumes')->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        $base = ResumeTemplate::query()->where('slug', 'classic-ats')->first();

        return view('admin.templates.form', [
            'template' => new ResumeTemplate([
                'category' => 'ats',
                'ats_status' => 'ats_safe',
                'is_active' => false,
                'supported_languages' => ['en', 'ar'],
                'sort_order' => ResumeTemplate::max('sort_order') + 1,
                'configuration' => $base?->configuration ?? ['layout' => 'single-column'],
            ]),
        ]);
    }

    public function store(TemplateRequest $request): RedirectResponse
    {
        $template = ResumeTemplate::create($request->attributesForModel());
        $this->registry->flush();
        $this->audit->record($request->user(), 'template.created', ['template' => $template->slug]);

        return redirect()->route('admin.templates.edit', $template)->with('status', __('admin.templates.saved'));
    }

    public function edit(ResumeTemplate $template): View
    {
        return view('admin.templates.form', compact('template'));
    }

    public function update(TemplateRequest $request, ResumeTemplate $template): RedirectResponse
    {
        $template->fill($request->attributesForModel());
        $template->version = $template->isDirty('configuration') ? $template->version + 1 : $template->version;
        $template->save();

        $this->registry->flush();
        $this->audit->record($request->user(), 'template.updated', ['template' => $template->slug]);

        return back()->with('status', __('admin.templates.saved'));
    }

    public function toggle(Request $request, ResumeTemplate $template): RedirectResponse
    {
        $template->forceFill(['is_active' => ! $template->is_active])->save();
        $this->registry->flush();
        $this->audit->record($request->user(), 'template.toggled', ['template' => $template->slug, 'active' => $template->is_active]);

        return back()->with('status', __('admin.templates.toggled'));
    }
}
