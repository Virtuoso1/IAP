<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property string $uuid
 * @property int $reporter_id
 * @property int $reported_user_id
 * @property string $reportable_type
 * @property int $reportable_id
 * @property int $category_id
 * @property string $severity
 * @property string|null $description
 * @property string $status
 * @property float $priority_score
 * @property bool $is_quarantined
 * @property int|null $moderator_id
 * @property \Carbon\Carbon|null $resolved_at
 * @property string|null $resolution_notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read User $reporter
 * @property-read User $reportedUser
 * @property-read User|null $moderator
 * @property-read ReportCategory $category
 * @property-read Model|Message|GroupMessage|Group $reportable
 * @property-read \Illuminate\Database\Eloquent\Collection|ReportEvidence[] $evidence
 * @property-read \Illuminate\Database\Eloquent\Collection|Warning[] $warnings */
class Report extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'reporter_id',
        'reported_user_id',
        'reportable_type',
        'reportable_id',
        'category_id',
        'severity',
        'description',
        'status',
        'priority_score',
        'is_quarantined',
        'moderator_id',
        'resolved_at',
        'resolution_notes'
    ];

    protected $casts = [
        'priority_score' => 'decimal:2',
        'is_quarantined' => 'boolean',
        'resolved_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $dates = [
        'resolved_at',
        'deleted_at'
    ];

    /**
     * Get the user who submitted the report.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Get the user who was reported.
     */
    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    /**
     * Get the moderator handling the report.
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /**
     * Get the report category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ReportCategory::class);
    }

    /**
     * Get the reported content (polymorphic).
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get evidence for this report.
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(ReportEvidence::class);
    }

    /**
     * Get warnings issued from this report.
     */
    public function warnings(): HasMany
    {
        return $this->hasMany(Warning::class);
    }

    /**
     * Scope to get pending reports.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get reports by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get reports by severity.
     */
    public function scopeBySeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to get high priority reports.
     */
    public function scopeHighPriority(Builder $query, float $threshold = 70): Builder
    {
        return $query->where('priority_score', '>=', $threshold);
    }

    /**
     * Scope to get quarantined reports.
     */
    public function scopeQuarantined(Builder $query): Builder
    {
        return $query->where('is_quarantined', true);
    }

    /**
     * Scope to get reports assigned to moderator.
     */
    public function scopeAssignedTo(Builder $query, int $moderatorId): Builder
    {
        return $query->where('moderator_id', $moderatorId);
    }

    /**
     * Scope to get unassigned reports.
     */
    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('moderator_id');
    }

    /**
     * Check if report is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Check if report is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if report is under review.
     */
    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    /**
     * Get formatted priority score.
     */
    public function getFormattedPriorityScoreAttribute(): string
    {
        return number_format($this->priority_score, 2);
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'under_review' => 'blue',
            'resolved' => 'green',
            'dismissed' => 'gray',
            'escalated' => 'red',
            default => 'gray'
        };
    }
}