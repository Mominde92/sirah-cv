<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ResumeSource;
use App\Enums\ResumeStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $template_id
 * @property int|null $parent_id
 * @property string $title
 * @property string $language
 * @property ResumeStatus $status
 * @property ResumeSource $source
 * @property array<string, mixed> $data Personal information block.
 * @property array<string, mixed>|null $styling User overrides of the template defaults.
 * @property int $version Optimistic-lock counter.
 * @property string|null $thumbnail_path
 * @property \Illuminate\Support\Carbon|null $last_edited_at
 */
class Resume extends Model
{
    use HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * Ownership (user_id) and the lock counter (version) are set by services only.
     *
     * @var list<string>
     */
    protected $fillable = [
        'template_id',
        'parent_id',
        'title',
        'language',
        'status',
        'source',
        'data',
        'styling',
        'thumbnail_path',
        'last_edited_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'source' => 'scratch',
        'version' => 1,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ResumeStatus::class,
            'source' => ResumeSource::class,
            'data' => 'array',
            'styling' => 'array',
            'version' => 'integer',
            'last_edited_at' => 'datetime',
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
    public function template(): BelongsTo
    {
        return $this->belongsTo(ResumeTemplate::class, 'template_id')->withTrashed();
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<ResumeSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(ResumeSection::class)->orderBy('position');
    }

    /**
     * @return HasMany<ResumeVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ResumeVersion::class)->latest('version');
    }

    /**
     * @return HasMany<ResumeExport, $this>
     */
    public function exports(): HasMany
    {
        return $this->hasMany(ResumeExport::class);
    }

    /**
     * @return HasMany<JobMatch, $this>
     */
    public function jobMatches(): HasMany
    {
        return $this->hasMany(JobMatch::class);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Text direction derived from the resume language.
     */
    public function direction(): string
    {
        return in_array($this->language, config('cv.rtl_locales', ['ar']), true) ? 'rtl' : 'ltr';
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ownedBy(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function recentlyEdited(Builder $query): void
    {
        $query->orderByDesc('updated_at');
    }
}
