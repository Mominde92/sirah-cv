<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Localization\LocaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale, LocaleService $locales): RedirectResponse
    {
        abort_unless($locales->isSupported($locale), 404);

        $request->session()->put(LocaleService::SESSION_KEY, $locale);

        $user = $request->user();

        if ($user instanceof User && $user->locale !== $locale) {
            $user->forceFill(['locale' => $locale])->save();
        }

        return back(fallback: route('home'));
    }
}
