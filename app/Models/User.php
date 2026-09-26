<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $avatar_path
 * @property string $locale
 * @property UserRole $role
 * @property int|null $subscription_id
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property \Illuminate\Support\Carbon|null $suspended_at
 */
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Role and subscription are deliberately not mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'avatar_path',
    ];

    /**
     * Mirrors the column defaults so freshly created instances are complete
     * (strict mode forbids reading attributes that were never loaded).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'avatar_path' => null,
        'locale' => 'en',
        'role' => 'user',
        'subscription_id' => null,
        'last_login_at' => null,
        'suspended_at' => null,
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * The current subscription. History is available through subscriptions().
     *
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<Resume, $this>
     */
    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    /**
     * @return HasMany<ResumeExport, $this>
     */
    public function exports(): HasMany
    {
        return $this->hasMany(ResumeExport::class);
    }

    /**
     * @return HasMany<ResumeImport, $this>
     */
    public function imports(): HasMany
    {
        return $this->hasMany(ResumeImport::class);
    }

    /**
     * @return HasMany<DesignReference, $this>
     */
    public function designReferences(): HasMany
    {
        return $this->hasMany(DesignReference::class);
    }

    /**
     * @return HasMany<JobMatch, $this>
     */
    public function jobMatches(): HasMany
    {
        return $this->hasMany(JobMatch::class);
    }

    /**
     * @return HasMany<FeatureUsage, $this>
     */
    public function featureUsages(): HasMany
    {
        return $this->hasMany(FeatureUsage::class);
    }

    /**
     * @return HasMany<AiUsageLog, $this>
     */
    public function aiUsageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class);
    }

    /**
     * Mail notifications (verification, password reset...) are sent in the user's language.
     */
    public function preferredLocale(): string
    {
        return $this->locale;
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/u', trim($this->name), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];

        return mb_strtoupper(implode('', array_map(
            fn (string $word): string => mb_substr($word, 0, 1),
            array_slice($words, 0, 2),
        )));
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function admins(Builder $query): void
    {
        $query->where('role', UserRole::Admin);
    }
}
