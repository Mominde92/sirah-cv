<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProcessingStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $resume_id
 * @property string|null $job_title
 * @property string|null $company
 * @property string $job_description
 * @property ProcessingStatus $status
 * @property array{keywords_found?: list<string>, keywords_missing?: list<string>, relevant_skills?: list<string>, suggestions?: list<array<string, mixed>>}|null $analysis
 * @property int|null $resume_version
 * @property string|null $error
 */
class JobMatch extends Model
{
    use HasFactory, HasPublicUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'resume_id',
        'job_title',
        'company',
        'job_description',
        'status',
        'analysis',
        'resume_version',
        'error',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProcessingStatus::class,
            'analysis' => 'array',
            'resume_version' => 'integer',
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
