<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiFeature;
use App\Enums\AiRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only record of one AI provider call.
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $resume_id
 * @property AiFeature $feature
 * @property string $provider
 * @property string $model
 * @property int $input_tokens
 * @property int $output_tokens
 * @property int|null $cost_micros
 * @property int|null $latency_ms
 * @property AiRequestStatus $status
 * @property string|null $error
 * @property \Illuminate\Support\Carbon $created_at
 */
class AiUsageLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'resume_id',
        'feature',
        'provider',
        'model',
        'input_tokens',
        'output_tokens',
        'cost_micros',
        'latency_ms',
        'status',
        'error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'feature' => AiFeature::class,
            'status' => AiRequestStatus::class,
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cost_micros' => 'integer',
            'latency_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Resume, $this>
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }
}
