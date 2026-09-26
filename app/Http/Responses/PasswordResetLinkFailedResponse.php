<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers "unknown email" exactly like "link sent", so the form cannot be used
 * to discover which addresses have an account. Throttling is still reported.
 */
final class PasswordResetLinkFailedResponse implements FailedPasswordResetLinkRequestResponse
{
    public function __construct(private readonly string $status)
    {
    }

    public function toResponse($request): Response
    {
        if ($this->status === Password::INVALID_USER) {
            return $request->wantsJson()
                ? new JsonResponse(['message' => __('passwords.sent')])
                : back()->with('status', __('passwords.sent'));
        }

        if ($request->wantsJson()) {
            throw ValidationException::withMessages(['email' => [__($this->status)]]);
        }

        return back()->withInput($request->only('email'))->withErrors(['email' => __($this->status)]);
    }
}
