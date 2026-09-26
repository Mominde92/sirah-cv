<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExportFormat;
use App\Enums\ProcessingStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $resume_id
 * @property ExportFormat $format
 * @property ProcessingStatus $status
 * @property int|null $resume_version
 * @property string $disk
 * @property string|null $path
 * @property int|null $file_size
 * @property array<string, mixed>|null $options
 * @property string|null $error
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $downloaded_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 */
class ResumeExport extends Model
{
    use HasFactory, HasPublicUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'resume_id',
        'format',
        'status',
        'resume_version',
        'disk',
        'path',
        'file_size',
        'options',
        'error',
        'started_at',
        'completed_at',
        'downloaded_at',
        'expires_at',
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
            'format' => ExportFormat::class,
            'status' => ProcessingStatus::class,
            'resume_version' => 'integer',
            'file_size' => 'integer',
            'options' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'downloaded_at' => 'datetime',
            'expires_at' => 'datetime',
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
        return $this->belongsTo(Resume::class)->withTrashed();
    }

    public function isDownloadable(): bool
    {
        return $this->status === ProcessingStatus::Completed
            && $this->path !== null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Completed exports whose file has passed its retention window.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function expired(Builder $query): void
    {
        $query->whereNotNull('path')->where('expires_at', '<=', now());
    }
}
