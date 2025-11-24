<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $moderator_id
 * @property int|null $report_id
 * @property int $warning_level
 * @property int $points
 * @property string $reason
 * @property bool $is_active
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $appeal_deadline
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read User $moderator
 * @property-read Report|null $report
 * @property-read \Illuminate\Database\Eloquent\Collection|Appeal[] $appeals */
class Warning extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'moderator_id',
        'report_id',
        'warning_level',
        'points',
        'reason',
        'is_active',
        'expires_at',
        'appeal_deadline'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'appeal_deadline' => 'datetime',
        'warning_level' => 'integer',
        'points' => 'integer'
    ];

    protected $dates = [
        'expires_at',
        'appeal_deadline'
    ];

    /**
     * Get the user who received the warning.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the moderator who issued the warning.
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /**
     * Get the associated report.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Get appeals for this warning.
     */
    public function appeals(): HasMany
    {
        return $this->hasMany(Appeal::class);
    }

    /**
     * Scope to get active warnings.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get expired warnings.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope to get warnings by level.
     */
    public function scopeByLevel(Builder $query, int $level): Builder
    {
        return $query->where('warning_level', $level);
    }

    /**
     * Scope to get warnings for user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if warning is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if warning can be appealed.
     */
    public function canBeAppealed(): bool
    {
        return $this->is_active && 
               (!$this->appeal_deadline || $this->appeal_deadline->isFuture());
    }

    /**
     * Check if warning is critical level.
     */
    public function isCritical(): bool
    {
        return $this->warning_level >= 3;
    }

    /**
     * Get warning level label.
     */
    public function getLevelLabelAttribute(): string
    {
        return match($this->warning_level) {
            1 => 'Level 1 - Informal Warning',
            2 => 'Level 2 - Formal Warning',
            3 => 'Level 3 - Temporary Suspension',
            4 => 'Level 4 - Permanent Ban',
            default => 'Unknown Level'
        };
    }

    /**
     * Get warning severity color.
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->warning_level) {
            1 => 'yellow',
            2 => 'orange',
            3 => 'red',
            4 => 'purple',
            default => 'gray'
        };
    }

    /**
     * Get days until expiration.
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return now()->diffInDays($this->expires_at);
    }

    /**
     * Get formatted expiration date.
     */
    public function getFormattedExpirationAttribute(): string
    {
        if (!$this->expires_at) {
            return 'Never';
        }

        return $this->expires_at->format('M j, Y H:i');
    }
}