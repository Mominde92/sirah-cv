<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProcessingStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An uploaded CV design (PDF/JPG/PNG) analysed for structure and style only.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property ProcessingStatus $status
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $file_size
 * @property array<string, mixed>|null $analysis
 * @property int|null $suggested_template_id
 * @property int|null $similarity
 * @property array<string, mixed>|null $generated_styling
 * @property int|null $resume_id
 * @property string|null $analyzer
 * @property string|null $error
 * @property \Illuminate\Support\Carbon|null $processed_at
 */
class DesignReference extends Model
{
    use HasFactory, HasPublicUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'status',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'file_size',
        'analysis',
        'suggested_template_id',
        'similarity',
        'generated_styling',
        'resume_id',
        'analyzer',
        'error',
        'processed_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'path',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'disk' => 'local',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProcessingStatus::class,
            'file_size' => 'integer',
            'analysis' => 'array',
            'similarity' => 'integer',
            'generated_styling' => 'array',
            'processed_at' => 'datetime',
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
     * @return BelongsTo<ResumeTemplate, $this>
     */
    public function suggestedTemplate(): BelongsTo
    {
        return $this->belongsTo(ResumeTemplate::class, 'suggested_template_id');
    }

    /**
     * @return BelongsTo<Resume, $this>
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }
}
