<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\JobMatch;
use App\Models\Resume;
use App\Services\JobMatching\JobMatchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class JobMatchController extends Controller
{
    public function create(Resume $resume): View
    {
        $this->authorize('update', $resume);

        return view('job-matches.create', [
            'resume' => $resume,
            'history' => $resume->jobMatches()->latest()->limit(8)->get(['id', 'uuid', 'job_title', 'company', 'analysis', 'created_at']),
        ]);
    }

    public function store(Request $request, Resume $resume, JobMatchService $matches): RedirectResponse
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'job_title' => ['nullable', 'string', 'max:200'],
            'company' => ['nullable', 'string', 'max:200'],
            'job_description' => ['required', 'string', 'min:80', 'max:20000'],
        ]);

        $match = $matches->analyze($request->user(), $resume, $validated['job_description'], $validated['job_title'] ?? null, $validated['company'] ?? null);

        return redirect()->route('job-matches.show', $match);
    }

    public function show(Request $request, JobMatch $jobMatch): View
    {
        abort_unless($jobMatch->user_id === $request->user()->id, 404);

        return view('job-matches.show', ['match' => $jobMatch->load('resume:id,uuid,title,language')]);
    }
}
