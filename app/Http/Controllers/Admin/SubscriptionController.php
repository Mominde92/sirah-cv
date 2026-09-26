<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class SubscriptionController extends Controller
{
    public function __invoke(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(SubscriptionStatus::class)]])['status'] ?? null;

        return view('admin.subscriptions.index', [
            'status' => $status,
            'subscriptions' => Subscription::query()
                ->with(['user:id,name,email', 'plan:id,name'])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(30)
                ->withQueryString(),
        ]);
    }
}
