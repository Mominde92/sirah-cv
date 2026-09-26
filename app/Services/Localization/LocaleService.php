<?php

declare(strict_types=1);

namespace App\Services\Localization;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Single source of truth for supported locales and text direction.
 * Adding a language = an entry in config/cv.php + a lang/<code> directory.
 */
final class LocaleService
{
    public const SESSION_KEY = 'locale';

    /**
     * @return array<string, array{name: string, native: string}>
     */
    public function all(): array
    {
        return config('cv.locales', []);
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_keys($this->all());
    }

    public function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, $this->all());
    }

    public function fallback(): string
    {
        $default = (string) config('app.locale', 'en');

        return $this->isSupported($default) ? $default : $this->codes()[0];
    }

    public function isRtl(?string $locale = null): bool
    {
        return in_array($locale ?? app()->getLocale(), config('cv.rtl_locales', []), true);
    }

    public function direction(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'rtl' : 'ltr';
    }

    public function nativeName(string $locale): string
    {
        return $this->all()[$locale]['native'] ?? $locale;
    }

    /**
     * Resolution order: signed-in user's preference, session choice, browser preference, app default.
     */
    public function resolve(Request $request): string
    {
        $user = $request->user();

        if ($user instanceof User && $this->isSupported($user->locale)) {
            return $user->locale;
        }

        $sessionLocale = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;

        if (is_string($sessionLocale) && $this->isSupported($sessionLocale)) {
            return $sessionLocale;
        }

        return $request->getPreferredLanguage($this->codes()) ?? $this->fallback();
    }
}
