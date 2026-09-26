<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\PasswordResetLinkFailedResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;

final class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Routes are declared in routes/auth.php so each endpoint has explicit middleware.
        Fortify::ignoreRoutes();

        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, PasswordResetLinkFailedResponse::class);
    }

    public function boot(): void
    {
        $this->registerActions();
        $this->registerViews();
        $this->registerAuthentication();
        $this->registerRateLimiters();
    }

    private function registerActions(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }

    private function registerViews(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));
    }

    /**
     * Same credential check as Fortify's default, plus a clear message for suspended accounts.
     * The suspension check runs only after the password matched, so it reveals nothing to guessers.
     */
    private function registerAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()->where('email', Str::lower((string) $request->input('email')))->first();

            if ($user === null || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }

            if ($user->isSuspended()) {
                throw ValidationException::withMessages(['email' => __('auth.suspended')]);
            }

            return $user;
        });
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $key = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('register', fn (Request $request): Limit => Limit::perHour(10)->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request): array => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perHour(5)->by('email:'.Str::lower((string) $request->input('email'))),
        ]);

        RateLimiter::for('verification', fn (Request $request): Limit => Limit::perMinute(6)->by(
            $request->user()?->getAuthIdentifier() ?? $request->ip()
        ));
    }
}
