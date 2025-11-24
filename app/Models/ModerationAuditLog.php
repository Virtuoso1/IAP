<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $log_hash
 * @property string|null $previous_log_hash
 * @property string $event_type
 * @property string $actor_type
 * @property int|null $actor_id
 * @property string $target_type
 * @property int|null $target_id
 * @property string $action
 * @property array|null $old_values
 * @property array|null $new_values
 * @property array|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $timestamp
 * @property-read User|null $actor */
class ModerationAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'log_hash',
        'previous_log_hash',
        'event_type',
        'actor_type',
        'actor_id',
        'target_type',
        'target_id',
        'action',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'timestamp'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'timestamp' => 'datetime'
    ];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * Get the actor who performed the action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Scope to get logs by event type.
     */
    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to get logs by actor.
     */
    public function scopeByActor($query, $actorType, $actorId = null)
    {
        $query->where('actor_type', $actorType);
        
        if ($actorId) {
            $query->where('actor_id', $actorId);
        }
        
        return $query;
    }

    /**
     * Scope to get logs by target.
     */
    public function scopeByTarget($query, $targetType, $targetId = null)
    {
        $query->where('target_type', $targetType);
        
        if ($targetId) {
            $query->where('target_id', $targetId);
        }
        
        return $query;
    }

    /**
     * Scope to get logs in date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent logs.
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('timestamp', '>=', now()->subHours($hours));
    }

    /**
     * Get formatted timestamp.
     */
    public function getFormattedTimestampAttribute(): string
    {
        return $this->timestamp->format('Y-m-d H:i:s.v');
    }

    /**
     * Get event type label.
     */
    public function getEventTypeLabelAttribute(): string
    {
        return match($this->event_type) {
            'report_created' => 'Report Created',
            'report_resolved' => 'Report Resolved',
            'warning_issued' => 'Warning Issued',
            'restriction_applied' => 'Restriction Applied',
            'appeal_submitted' => 'Appeal Submitted',
            'appeal_reviewed' => 'Appeal Reviewed',
            'moderator_action' => 'Moderator Action',
            'system_event' => 'System Event',
            default => 'Unknown Event'
        };
    }

    /**
     * Get action type label.
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'create' => 'Created',
            'update' => 'Updated',
            'delete' => 'Deleted',
            'resolve' => 'Resolved',
            'dismiss' => 'Dismissed',
            'escalate' => 'Escalated',
            'issue_warning' => 'Issue Warning',
            'apply_restriction' => 'Apply Restriction',
            'lift_restriction' => 'Lift Restriction',
            'submit_appeal' => 'Submit Appeal',
            'review_appeal' => 'Review Appeal',
            default => 'Unknown Action'
        };
    }
}