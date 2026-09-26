<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Feature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Monthly counter for a metered feature.
 *
 * @property int $id
 * @property int $user_id
 * @property Feature $feature
 * @property \Illuminate\Support\Carbon $period_start
 * @property int $used
 */
class FeatureUsage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'feature',
        'period_start',
        'used',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'feature' => Feature::class,
            'period_start' => 'date',
            'used' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
