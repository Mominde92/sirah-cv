<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Metadata only: resume content stays private to its owner.
 */
final class ResumeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $resumes = Resume::query()
            ->with(['user:id,name,email', 'template:id,name'])
            ->when($search !== '', fn ($query) => $query->whereHas('user', fn ($q) => $q->where('email', 'like', "%{$search}%")))
            ->latest('updated_at')
            ->paginate(30, ['id', 'uuid', 'user_id', 'template_id', 'title', 'language', 'status', 'source', 'created_at', 'updated_at'])
            ->withQueryString();

        return view('admin.resumes.index', compact('resumes', 'search'));
    }
}
