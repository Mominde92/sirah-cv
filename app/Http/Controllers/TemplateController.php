<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Feature;
use App\Services\Templates\TemplateRegistry;
use App\Services\Usage\FeatureGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class TemplateController extends Controller
{
    public function index(Request $request, TemplateRegistry $templates, FeatureGate $gate): View
    {
        return view('templates.index', [
            'templates' => $templates->active(),
            'canUsePremium' => $request->user() !== null && $gate->allows($request->user(), Feature::PremiumTemplates),
        ]);
    }
}
