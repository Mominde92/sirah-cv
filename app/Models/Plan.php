<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Feature;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property int $price_monthly
 * @property int $price_yearly
 * @property string $currency
 * @property int $trial_days
 * @property array<string, mixed>|null $provider_price_ids
 * @property bool $is_active
 * @property bool $is_public
 * @property bool $is_default
 * @property int $sort_order
 */
class Plan extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    /**
     * @var list<string>
     */
    public array $translatable = ['name', 'description'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'trial_days',
        'provider_price_ids',
        'is_active',
        'is_public',
        'is_default',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_monthly' => 'integer',
            'price_yearly' => 'integer',
            'trial_days' => 'integer',
            'provider_price_ids' => 'array',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<PlanFeature, $this>
     */
    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function feature(Feature $feature): ?PlanFeature
    {
        return $this->features->firstWhere('feature', $feature);
    }

    public function isFree(): bool
    {
        return $this->price_monthly === 0 && $this->price_yearly === 0;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function listed(Builder $query): void
    {
        $query->where('is_active', true)->where('is_public', true)->orderBy('sort_order');
    }
}
