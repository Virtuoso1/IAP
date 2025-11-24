<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property int $user_id
 * @property string $restriction_type
 * @property string $severity
 * @property string $reason
 * @property \Carbon\Carbon $starts_at
 * @property \Carbon\Carbon|null $ends_at
 * @property bool $is_active
 * @property int $moderator_id
 * @property int|null $warning_id
 * @property string|null $ip_address
 * @property string|null $device_fingerprint
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read User $moderator
 * @property-read Warning|null $warning */
class UserRestriction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'restriction_type',
        'severity',
        'reason',
        'starts_at',
        'ends_at',
        'is_active',
        'moderator_id',
        'warning_id',
        'ip_address',
        'device_fingerprint'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime'
    ];

    protected $dates = [
        'starts_at',
        'ends_at'
    ];

    /**
     * Get the restricted user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the moderator who applied the restriction.
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /**
     * Get the associated warning.
     */
    public function warning(): BelongsTo
    {
        return $this->belongsTo(Warning::class);
    }

    /**
     * Scope to get active restrictions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('ends_at')
                          ->orWhere('ends_at', '>', now());
                    });
    }

    /**
     * Scope to get expired restrictions.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('ends_at', '<', now());
    }

    /**
     * Scope to get permanent restrictions.
     */
    public function scopePermanent(Builder $query): Builder
    {
        return $query->where('severity', 'permanent');
    }

    /**
     * Scope to get temporary restrictions.
     */
    public function scopeTemporary(Builder $query): Builder
    {
        return $query->where('severity', 'temporary');
    }

    /**
     * Scope to get restrictions by type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('restriction_type', $type);
    }

    /**
     * Check if restriction is currently active.
     */
    public function isActive(): bool
    {
        return $this->is_active && 
               (!$this->ends_at || $this->ends_at->isFuture());
    }

    /**
     * Check if restriction is permanent.
     */
    public function isPermanent(): bool
    {
        return $this->severity === 'permanent';
    }

    /**
     * Check if restriction is expired.
     */
    public function isExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    /**
     * Get remaining time in hours.
     */
    public function getRemainingHoursAttribute(): ?int
    {
        if ($this->isPermanent() || !$this->ends_at) {
            return null;
        }

        return now()->diffInHours($this->ends_at);
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->isPermanent()) {
            return 'Permanent';
        }

        if (!$this->ends_at) {
            return 'Unknown';
        }

        $duration = $this->starts_at->diffForHumans($this->ends_at, true);
        return ucfirst($duration);
    }

    /**
     * Get restriction type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->restriction_type) {
            'posting' => 'Posting Restrictions',
            'messaging' => 'Messaging Restrictions',
            'group_creation' => 'Group Creation Restrictions',
            'profile_view' => 'Profile View Restrictions',
            'full_access' => 'Full Access Restrictions',
            'reporting' => 'Reporting Restrictions',
            default => 'Unknown Restriction'
        };
    }

    /**
     * Get severity color.
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'warning' => 'yellow',
            'temporary' => 'orange',
            'permanent' => 'red',
            default => 'gray'
        };
    }
}