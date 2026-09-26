<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProcessingStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An uploaded CV (PDF/DOCX). The original file and full extracted text are always kept.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $resume_id
 * @property ProcessingStatus $status
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $file_size
 * @property string|null $extracted_text
 * @property array<string, mixed>|null $parsed_data
 * @property list<array{field: string, reason: string, snippet?: string}>|null $review_flags
 * @property string|null $parser
 * @property string|null $error
 * @property \Illuminate\Support\Carbon|null $processed_at
 */
class ResumeImport extends Model
{
    use HasFactory, HasPublicUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'resume_id',
        'status',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'file_size',
        'extracted_text',
        'parsed_data',
        'review_flags',
        'parser',
        'error',
        'processed_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'path',
        'extracted_text',
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
            'parsed_data' => 'array',
            'review_flags' => 'array',
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
     * @return BelongsTo<Resume, $this>
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function needsReview(): bool
    {
        return $this->status === ProcessingStatus::NeedsReview || ! empty($this->review_flags);
    }
}
