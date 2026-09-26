<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AtsStatus;
use App\Enums\TemplateCategory;
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
 * @property TemplateCategory $category
 * @property AtsStatus $ats_status
 * @property string $layout
 * @property string|null $preview_path
 * @property string|null $thumbnail_path
 * @property list<string> $supported_languages
 * @property array<string, mixed> $configuration
 * @property bool $is_premium
 * @property bool $is_active
 * @property int $sort_order
 * @property int $version
 */
class ResumeTemplate extends Model
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
        'category',
        'ats_status',
        'layout',
        'preview_path',
        'thumbnail_path',
        'supported_languages',
        'configuration',
        'is_premium',
        'is_active',
        'sort_order',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => TemplateCategory::class,
            'ats_status' => AtsStatus::class,
            'supported_languages' => 'array',
            'configuration' => 'array',
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'version' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Resume, $this>
     */
    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class, 'template_id');
    }

    public function supportsLanguage(string $language): bool
    {
        return in_array($language, $this->supported_languages, true);
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
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function atsFriendly(Builder $query): void
    {
        $query->whereIn('ats_status', [AtsStatus::AtsSafe, AtsStatus::AtsOptimized]);
    }
}
