<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Setting;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Admin-editable settings stored in the settings table, cached as one map.
 */
final class SettingsService
{
    private const CACHE_KEY = 'settings.all';

    /**
     * Known settings with their defaults and validation rules.
     *
     * @var array<string, array{default: mixed, rules: list<string>}>
     */
    public const DEFINITIONS = [
        'general.support_email' => ['default' => null, 'rules' => ['nullable', 'email', 'max:190']],
        'general.announcement_en' => ['default' => null, 'rules' => ['nullable', 'string', 'max:300']],
        'general.announcement_ar' => ['default' => null, 'rules' => ['nullable', 'string', 'max:300']],
        'general.registration_open' => ['default' => true, 'rules' => ['boolean']],
        'ai.enabled' => ['default' => true, 'rules' => ['boolean']],
    ];

    public function __construct(private readonly Cache $cache)
    {
    }

    public function get(string $key): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : (self::DEFINITIONS[$key]['default'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->cache->rememberForever(self::CACHE_KEY, fn (): array => Setting::query()->get()
            ->mapWithKeys(fn (Setting $setting): array => [$setting->group.'.'.$setting->key => $setting->value])
            ->all());
    }

    /**
     * @param  array<string, mixed>  $values  key => value
     */
    public function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFINITIONS)) {
                continue;
            }

            [$group, $name] = explode('.', $key, 2);
            Setting::query()->updateOrCreate(['group' => $group, 'key' => $name], ['value' => $value]);
        }

        $this->cache->forget(self::CACHE_KEY);
    }
}
