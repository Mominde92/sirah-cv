<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResumeExport;
use Illuminate\Contracts\View\View;

final class DownloadController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.downloads.index', [
            'summary' => ResumeExport::query()
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('format, status, count(*) as total')
                ->groupBy('format', 'status')
                ->get(),
            'exports' => ResumeExport::query()->with(['user:id,email', 'resume:id,title'])->latest()->paginate(30),
        ]);
    }
}
