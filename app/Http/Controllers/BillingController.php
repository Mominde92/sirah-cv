<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BillingInterval;
use App\Services\Billing\PlanResolver;
use App\Services\Billing\SubscriptionService;
use App\Services\Usage\FeatureGate;
use App\Services\Usage\UsageMeter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

final class BillingController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions)
    {
    }

    public function index(Request $request, PlanResolver $plans, FeatureGate $gate, UsageMeter $meter): View
    {
        $user = $request->user()->load('subscription.plan');

        return view('billing.index', [
            'plan' => $plans->forUser($user),
            'subscription' => $user->subscription?->isValid() ? $user->subscription : null,
            'plans' => $plans->listed(),
            'usage' => $gate->summary($user),
            'periodEndsAt' => $meter->currentPeriodEnd(),
            'payments' => $user->payments()->latest()->limit(10)->get(),
            'checkoutEnabled' => $this->subscriptions->gateway()->enabled(),
        ]);
    }

    public function checkout(Request $request, PlanResolver $plans): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'string'],
            'interval' => ['required', Rule::enum(BillingInterval::class)],
        ]);

        $plan = $plans->listed()->firstWhere('slug', $validated['plan']);
        abort_if($plan === null || $plan->isFree(), 404);

        if (! $this->subscriptions->gateway()->enabled()) {
            return back()->with('status', __('billing.page.contact_sales'));
        }

        try {
            $url = $this->subscriptions->gateway()->checkoutUrl($request->user(), $plan, BillingInterval::from($validated['interval']));
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['billing' => __('billing.page.checkout_failed')]);
        }

        return redirect()->away($url);
    }

    public function success(): RedirectResponse
    {
        // The subscription itself is activated by the signed webhook, not by this redirect.
        return redirect()->route('billing.index')->with('status', __('billing.page.thanks'));
    }

    public function cancel(Request $request): RedirectResponse
    {
        $subscription = $request->user()->subscription;
        abort_if($subscription === null || ! $subscription->isValid(), 404);

        try {
            $this->subscriptions->cancel($subscription);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['billing' => __('billing.page.action_failed')]);
        }

        return back()->with('status', __('billing.page.canceled', ['date' => $subscription->ends_at?->translatedFormat('j F Y')]));
    }

    public function resume(Request $request): RedirectResponse
    {
        $subscription = $request->user()->subscription;
        abort_if($subscription === null || $subscription->canceled_at === null, 404);

        try {
            $this->subscriptions->resume($subscription);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['billing' => __('billing.page.action_failed')]);
        }

        return back()->with('status', __('billing.page.resumed'));
    }
}
