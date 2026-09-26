<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Feature;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Admin\AdminAudit;
use App\Services\Billing\PlanResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class PlanController extends Controller
{
    public function __construct(
        private readonly PlanResolver $plans,
        private readonly AdminAudit $audit,
    ) {
    }

    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => Plan::query()->with('features')->withCount(['subscriptions as active_subscriptions_count' => fn ($query) => $query->valid()])->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.plans.form', ['plan' => new Plan(['currency' => 'USD', 'is_active' => true, 'is_public' => true, 'sort_order' => Plan::max('sort_order') + 1])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $plan = DB::transaction(fn (): Plan => $this->save(new Plan(), $request));
        $this->audit->record($request->user(), 'plan.created', ['plan' => $plan->slug]);

        return redirect()->route('admin.plans.edit', $plan)->with('status', __('admin.plans.saved'));
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.form', ['plan' => $plan->load('features')]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        DB::transaction(fn (): Plan => $this->save($plan, $request));
        $this->audit->record($request->user(), 'plan.updated', ['plan' => $plan->slug]);

        return back()->with('status', __('admin.plans.saved'));
    }

    private function save(Plan $plan, Request $request): Plan
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/', Rule::unique('plans', 'slug')->ignore($plan->id)],
            'name_en' => ['required', 'string', 'max:80'],
            'name_ar' => ['required', 'string', 'max:80'],
            'description_en' => ['nullable', 'string', 'max:300'],
            'description_ar' => ['nullable', 'string', 'max:300'],
            'price_monthly' => ['required', 'numeric', 'min:0', 'max:100000'],
            'price_yearly' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'currency' => ['required', 'string', 'size:3'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:90'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'stripe_monthly' => ['nullable', 'string', 'max:120'],
            'stripe_yearly' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
            'is_public' => ['boolean'],
            'is_default' => ['boolean'],
            'features' => ['array'],
            'features.*.enabled' => ['boolean'],
            'features.*.limit' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);

        $plan->fill([
            'slug' => $validated['slug'],
            'name' => ['en' => $validated['name_en'], 'ar' => $validated['name_ar']],
            'description' => ['en' => (string) ($validated['description_en'] ?? ''), 'ar' => (string) ($validated['description_ar'] ?? '')],
            'price_monthly' => (int) round($validated['price_monthly'] * 100),
            'price_yearly' => (int) round($validated['price_yearly'] * 100),
            'currency' => strtoupper($validated['currency']),
            'trial_days' => (int) $validated['trial_days'],
            'sort_order' => (int) $validated['sort_order'],
            'provider_price_ids' => ['stripe' => ['monthly' => $validated['stripe_monthly'] ?? null, 'yearly' => $validated['stripe_yearly'] ?? null]],
            'is_active' => $request->boolean('is_active'),
            'is_public' => $request->boolean('is_public'),
            'is_default' => $request->boolean('is_default'),
        ])->save();

        // Exactly one default plan: the fallback for users without a subscription.
        if ($plan->is_default) {
            Plan::query()->whereKeyNot($plan->id)->update(['is_default' => false]);
        }

        foreach (Feature::cases() as $feature) {
            $input = $validated['features'][$feature->value] ?? [];
            $limit = $input['limit'] ?? null;

            $plan->features()->updateOrCreate(
                ['feature' => $feature],
                ['is_enabled' => (bool) ($input['enabled'] ?? false), 'limit_value' => $feature->isBoolean() || $limit === null || $limit === '' ? null : (int) $limit],
            );
        }

        $this->plans->flush();

        return $plan;
    }
}
