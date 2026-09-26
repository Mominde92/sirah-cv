<?php

declare(strict_types=1);

namespace App\Services\Templates;

use App\Enums\Feature;
use App\Models\ResumeTemplate;
use App\Models\User;
use App\Services\Usage\FeatureGate;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\Collection;

/**
 * Cached catalogue of active templates and the rules for who may use which.
 */
final class TemplateRegistry
{
    private const CACHE_KEY = 'templates.active';

    public function __construct(
        private readonly Cache $cache,
        private readonly FeatureGate $gate,
    ) {
    }

    /**
     * @return Collection<int, ResumeTemplate>
     */
    public function active(): Collection
    {
        return $this->cache->rememberForever(
            self::CACHE_KEY,
            fn (): Collection => ResumeTemplate::query()->active()->ordered()->get(),
        );
    }

    public function findActive(int $id): ?ResumeTemplate
    {
        return $this->active()->firstWhere('id', $id);
    }

    public function findActiveBySlug(string $slug): ?ResumeTemplate
    {
        return $this->active()->firstWhere('slug', $slug);
    }

    public function default(): ?ResumeTemplate
    {
        return $this->active()->first(fn (ResumeTemplate $template): bool => ! $template->is_premium) ?? $this->active()->first();
    }

    public function isAvailableTo(User $user, ResumeTemplate $template): bool
    {
        return $template->is_active
            && (! $template->is_premium || $this->gate->allows($user, Feature::PremiumTemplates));
    }

    public function flush(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }
}
