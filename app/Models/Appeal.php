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
 * @property int|null $warning_id
 * @property int|null $restriction_id
 * @property int|null $report_id
 * @property string $appeal_type
 * @property string $reason
 * @property array|null $evidence
 * @property string $status
 * @property int|null $reviewer_id
 * @property string|null $review_notes
 * @property string|null $final_decision
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read User|null $reviewer
 * @property-read Warning|null $warning
 * @property-read UserRestriction|null $restriction
 * @property-read Report|null $report */
class Appeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'warning_id',
        'restriction_id',
        'report_id',
        'appeal_type',
        'reason',
        'evidence',
        'status',
        'reviewer_id',
        'review_notes',
        'final_decision'
    ];

    protected $casts = [
        'evidence' => 'array'
    ];

    /**
     * Get the user who submitted the appeal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reviewer who handled the appeal.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the associated warning.
     */
    public function warning(): BelongsTo
    {
        return $this->belongsTo(Warning::class);
    }

    /**
     * Get the associated restriction.
     */
    public function restriction(): BelongsTo
    {
        return $this->belongsTo(UserRestriction::class);
    }

    /**
     * Get the associated report.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Scope to get appeals by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending appeals.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get appeals under review.
     */
    public function scopeUnderReview(Builder $query): Builder
    {
        return $query->where('status', 'under_review');
    }

    /**
     * Scope to get appeals by type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('appeal_type', $type);
    }

    /**
     * Check if appeal is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if appeal is under review.
     */
    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    /**
     * Check if appeal is approved.
     */
    public function isApproved(): bool
    {
        return $this->final_decision === 'approve' || $this->final_decision === 'modify';
    }

    /**
     * Check if appeal is denied.
     */
    public function isDenied(): bool
    {
        return $this->final_decision === 'deny';
    }

    /**
     * Get appeal type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->appeal_type) {
            'warning' => 'Warning Appeal',
            'restriction' => 'Restriction Appeal',
            'ban' => 'Ban Appeal',
            default => 'Unknown Appeal Type'
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending Review',
            'under_review' => 'Under Review',
            'approved' => 'Approved',
            'denied' => 'Denied',
            'escalated' => 'Escalated',
            default => 'Unknown Status'
        };
    }

    /**
     * Get status color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'under_review' => 'blue',
            'approved' => 'green',
            'denied' => 'red',
            'escalated' => 'purple',
            default => 'gray'
        };
    }

    /**
     * Get final decision label.
     */
    public function getFinalDecisionLabelAttribute(): string
    {
        return match($this->final_decision) {
            'uphold' => 'Upheld Original Decision',
            'reverse' => 'Reversed Original Decision',
            'modify' => 'Modified Original Decision',
            default => 'No Final Decision'
        };
    }
}