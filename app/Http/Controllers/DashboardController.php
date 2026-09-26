<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Feature;
use App\Services\Billing\PlanResolver;
use App\Services\Usage\FeatureGate;
use App\Services\Usage\UsageMeter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    /**
     * Features shown as usage meters, in display order.
     */
    private const METERED_ON_DASHBOARD = [
        Feature::Resumes,
        Feature::PdfExport,
        Feature::DocxExport,
        Feature::AiRequests,
    ];

    public function __invoke(Request $request, PlanResolver $plans, FeatureGate $gate, UsageMeter $meter): View
    {
        $user = $request->user()->load('subscription');
        $summary = $gate->summary($user);

        return view('dashboard.index', [
            'user' => $user,
            'plan' => $plans->forUser($user),
            'subscription' => $user->subscription?->isValid() ? $user->subscription : null,
            'usage' => array_map(fn (Feature $feature) => $summary[$feature->value], self::METERED_ON_DASHBOARD),
            'periodEndsAt' => $meter->currentPeriodEnd(),
            'recentResumes' => $user->resumes()
                ->with('template:id,slug,name,ats_status')
                ->select(['id', 'uuid', 'user_id', 'template_id', 'title', 'language', 'updated_at'])
                ->recentlyEdited()
                ->limit(3)
                ->get(),
            'greeting' => $this->greeting(),
        ]);
    }

    private function greeting(): string
    {
        return match (true) {
            now()->hour < 12 => 'morning',
            now()->hour < 18 => 'afternoon',
            default => 'evening',
        };
    }
}
