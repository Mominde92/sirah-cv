<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Contracts\PdfRenderer;
use App\Services\Billing\Gateways\ManualGateway;
use App\Services\Billing\Gateways\StripeGateway;
use App\Models\User;
use App\Services\Ai\AiManager;
use App\Services\Export\Pdf\BrowsershotRenderer;
use App\Services\Export\Pdf\MpdfRenderer;
use App\Services\Billing\PlanResolver;
use App\Services\Localization\LocaleService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LocaleService::class);
        // Scoped: plans are read from cache once per request / queued job.
        $this->app->scoped(PlanResolver::class);
        $this->app->singleton(AiManager::class);

        // Stripe when its keys are configured, otherwise admin-granted plans only.
        $this->app->bind(PaymentGateway::class, fn ($app): PaymentGateway => $app->make(StripeGateway::class)->enabled()
            ? $app->make(StripeGateway::class)
            : $app->make(ManualGateway::class));

        // PDF_DRIVER=mpdf (pure PHP, default) or browsershot (headless Chromium).
        $this->app->bind(PdfRenderer::class, fn ($app): PdfRenderer => match (config('cv.pdf.driver')) {
            'browsershot' => $app->make(BrowsershotRenderer::class),
            default => $app->make(MpdfRenderer::class),
        });
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Password::defaults(function (): Password {
            $rule = Password::min(8)->max(128)->letters()->numbers();

            // Breached-password check (Have I Been Pwned range API, k-anonymity) needs network access.
            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        Gate::define('access-admin', fn (User $user): bool => $user->isAdmin() && ! $user->isSuspended());

        $this->registerRateLimiters();

        Vite::prefetch(concurrency: 3);
    }

    /**
     * Per-user limits for expensive endpoints (plan quotas are enforced separately by FeatureGate).
     */
    private function registerRateLimiters(): void
    {
        $byUser = fn (Request $request): string => (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());

        RateLimiter::for('exports', fn (Request $request): Limit => Limit::perMinute(10)->by($byUser($request)));
        RateLimiter::for('uploads', fn (Request $request): Limit => Limit::perMinute(6)->by($byUser($request)));
        RateLimiter::for('ai', fn (Request $request): Limit => Limit::perMinute(20)->by($byUser($request)));
    }
}
