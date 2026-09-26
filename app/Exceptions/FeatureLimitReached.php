<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Data\FeatureAllowance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Thrown when the user's plan does not allow an action. Rendered as an upgrade prompt.
 */
final class FeatureLimitReached extends RuntimeException
{
    public function __construct(public readonly FeatureAllowance $allowance)
    {
        parent::__construct($this->userMessage());
    }

    public function userMessage(): string
    {
        $feature = $this->allowance->feature->label();

        return $this->allowance->enabled
            ? __('billing.limit_reached', ['feature' => $feature, 'limit' => $this->allowance->limit])
            : __('billing.feature_unavailable', ['feature' => $feature]);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => $this->userMessage(),
                'feature' => $this->allowance->feature->value,
                'limit' => $this->allowance->limit,
                'used' => $this->allowance->used,
                'upgrade_url' => route('home').'#pricing',
            ], 403);
        }

        return back()->with('upgrade', [
            'message' => $this->userMessage(),
            'feature' => $this->allowance->feature->value,
        ]);
    }
}
