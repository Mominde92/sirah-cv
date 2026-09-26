<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AiRequestStatus;
use App\Enums\ExportFormat;
use App\Enums\PaymentStatus;
use App\Enums\ProcessingStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\Payment;
use App\Models\Resume;
use App\Models\ResumeExport;
use App\Models\ResumeImport;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

final class OverviewController extends Controller
{
    public function __invoke(): View
    {
        // Counters are cheap aggregates on indexed columns; cached briefly to keep the page instant.
        $stats = Cache::remember('admin.overview', 60, fn (): array => [
            'users' => User::count(),
            'users_30d' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'resumes' => Resume::count(),
            'resumes_30d' => Resume::where('created_at', '>=', now()->subDays(30))->count(),
            'subscriptions' => Subscription::whereIn('status', SubscriptionStatus::grantingAccess())->count(),
            'revenue_30d' => (int) Payment::where('status', PaymentStatus::Succeeded)->where('paid_at', '>=', now()->subDays(30))->sum('amount'),
            'pdf_30d' => ResumeExport::where('format', ExportFormat::Pdf)->where('status', ProcessingStatus::Completed)->where('created_at', '>=', now()->subDays(30))->count(),
            'docx_30d' => ResumeExport::where('format', ExportFormat::Docx)->where('status', ProcessingStatus::Completed)->where('created_at', '>=', now()->subDays(30))->count(),
            'exports_failed_30d' => ResumeExport::where('status', ProcessingStatus::Failed)->where('created_at', '>=', now()->subDays(30))->count(),
            'imports_30d' => ResumeImport::where('created_at', '>=', now()->subDays(30))->count(),
            'ai_30d' => AiUsageLog::where('status', AiRequestStatus::Succeeded)->where('created_at', '>=', now()->subDays(30))->count(),
            'ai_tokens_30d' => (int) AiUsageLog::where('created_at', '>=', now()->subDays(30))->sum('output_tokens'),
        ]);

        return view('admin.overview', [
            'stats' => $stats,
            'latestUsers' => User::latest()->limit(6)->get(['id', 'name', 'email', 'created_at', 'email_verified_at']),
            'topTemplates' => Resume::query()->selectRaw('template_id, count(*) as total')->groupBy('template_id')->orderByDesc('total')->with('template:id,name,slug')->limit(5)->get(),
        ]);
    }
}
