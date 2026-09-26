<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable snapshot of a resume document.
 *
 * @property int $id
 * @property int $resume_id
 * @property int $version
 * @property string|null $label
 * @property array{template_id: int, data: array<string, mixed>, styling: array<string, mixed>|null, sections: list<array<string, mixed>>} $snapshot
 * @property \Illuminate\Support\Carbon $created_at
 */
class ResumeVersion extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'version',
        'label',
        'snapshot',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Resume, $this>
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }
}
