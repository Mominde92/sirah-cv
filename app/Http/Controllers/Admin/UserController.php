<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Services\Admin\AdminAudit;
use App\Services\Billing\PlanResolver;
use App\Services\Billing\SubscriptionService;
use App\Services\Usage\FeatureGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class UserController extends Controller
{
    public function __construct(private readonly AdminAudit $audit)
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $filter = (string) $request->query('filter');

        $users = User::query()
            ->with('subscription.plan:id,name,slug')
            ->withCount('resumes')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
            ->when($filter === 'admins', fn ($query) => $query->where('role', UserRole::Admin))
            ->when($filter === 'suspended', fn ($query) => $query->whereNotNull('suspended_at'))
            ->when($filter === 'unverified', fn ($query) => $query->whereNull('email_verified_at'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search', 'filter'));
    }

    public function show(User $user, PlanResolver $plans, FeatureGate $gate): View
    {
        return view('admin.users.show', [
            'user' => $user->loadCount(['resumes', 'exports', 'imports', 'aiUsageLogs']),
            'plan' => $plans->forUser($user),
            'subscriptions' => $user->subscriptions()->with('plan:id,name')->latest()->limit(10)->get(),
            'resumes' => $user->resumes()->with('template:id,name')->latest('updated_at')->limit(10)->get(['id', 'uuid', 'user_id', 'template_id', 'title', 'language', 'updated_at']),
            'usage' => $gate->summary($user),
            'plans' => Plan::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, __('admin.users.cannot_self'));

        $user->forceFill(['suspended_at' => now()])->save();
        $this->audit->record($request->user(), 'user.suspended', ['user_id' => $user->id]);

        return back()->with('status', __('admin.users.suspended'));
    }

    public function unsuspend(Request $request, User $user): RedirectResponse
    {
        $user->forceFill(['suspended_at' => null])->save();
        $this->audit->record($request->user(), 'user.unsuspended', ['user_id' => $user->id]);

        return back()->with('status', __('admin.users.unsuspended'));
    }

    public function grantPlan(Request $request, User $user, SubscriptionService $subscriptions): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', Rule::exists('plans', 'id')->whereNull('deleted_at')],
            'months' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $subscriptions->grant($user, $plan, isset($validated['months']) ? (int) $validated['months'] : null);
        $this->audit->record($request->user(), 'user.plan_granted', ['user_id' => $user->id, 'plan' => $plan->slug, 'months' => $validated['months'] ?? null]);

        return back()->with('status', __('admin.users.plan_granted', ['plan' => $plan->name]));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, __('admin.users.cannot_self'));

        $validated = $request->validate(['role' => ['required', Rule::enum(UserRole::class)]]);

        $user->forceFill(['role' => $validated['role']])->save();
        $this->audit->record($request->user(), 'user.role_changed', ['user_id' => $user->id, 'role' => $validated['role']]);

        return back()->with('status', __('admin.users.role_updated'));
    }
}
