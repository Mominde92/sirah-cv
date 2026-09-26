<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Feature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $plan_id
 * @property Feature $feature
 * @property bool $is_enabled
 * @property int|null $limit_value NULL means unlimited.
 */
class PlanFeature extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'plan_id',
        'feature',
        'is_enabled',
        'limit_value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'feature' => Feature::class,
            'is_enabled' => 'boolean',
            'limit_value' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isUnlimited(): bool
    {
        return $this->is_enabled && $this->limit_value === null;
    }
}
