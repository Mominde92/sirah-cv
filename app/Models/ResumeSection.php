<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SectionType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $resume_id
 * @property SectionType $type
 * @property string|null $title
 * @property int $position
 * @property bool $is_visible
 * @property string|null $column
 * @property list<array<string, mixed>> $items
 * @property array<string, mixed>|null $settings
 */
class ResumeSection extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'title',
        'position',
        'is_visible',
        'column',
        'items',
        'settings',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_visible' => true,
        'items' => '[]',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SectionType::class,
            'position' => 'integer',
            'is_visible' => 'boolean',
            'items' => 'array',
            'settings' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Resume, $this>
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    /**
     * Heading shown on the resume: the custom title, or the standard name in the resume's language.
     */
    public function displayTitle(?string $locale = null): string
    {
        return filled($this->title) ? $this->title : $this->type->defaultTitle($locale);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_visible', true);
    }
}
