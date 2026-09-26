<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Billing\PlanResolver;
use App\Services\Templates\TemplateRegistry;
use Illuminate\Contracts\View\View;

final class LandingController extends Controller
{
    public function __invoke(PlanResolver $plans, TemplateRegistry $templates): View
    {
        return view('landing.index', [
            'plans' => $plans->listed(),
            'templates' => $templates->active()->take(8),
        ]);
    }
}
