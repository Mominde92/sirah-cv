<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\Admin\SettingsService;
use App\Services\Localization\LocaleService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(private readonly LocaleService $locales)
    {
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        if (! app(SettingsService::class)->get('general.registration_open')) {
            throw ValidationException::withMessages(['email' => __('auth.register.closed')]);
        }

        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'terms' => ['accepted'],
        ])->validate();

        // New accounts start on the default plan implicitly (PlanResolver fallback),
        // so no subscription row is created here.
        return User::create([
            'name' => trim($validated['name']),
            'email' => mb_strtolower($validated['email']),
            'password' => $validated['password'],
            'locale' => $this->locales->isSupported(app()->getLocale()) ? app()->getLocale() : $this->locales->fallback(),
        ]);
    }
}
