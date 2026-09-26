<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Localization\LocaleService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public function __construct(private readonly LocaleService $locales)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->locales->resolve($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        $response = $next($request);

        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
