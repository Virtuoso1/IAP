<?php

namespace App\Listeners;

use App\Services\AuditLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class LogModerationActivity implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        try {
            $this->logModerationEvent($event);
        } catch (\Exception $e) {
            Log::error('Failed to log moderation activity', [
                'event_class' => get_class($event),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(object $event, \Throwable $exception): void
    {
        Log::error('Moderation activity logging failed', [
            'event_class' => get_class($event),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Log moderation events based on event type.
     */
    protected function logModerationEvent(object $event): void
    {
        $eventName = class_basename($event);
        
        switch ($eventName) {
            case 'ReportSubmitted':
                $this->logReportSubmitted($event);
                break;

            case 'ReportResolved':
                $this->logReportResolved($event);
                break;

            case 'WarningIssued':
                $this->logWarningIssued($event);
                break;

            case 'RestrictionApplied':
                $this->logRestrictionApplied($event);
                break;

            case 'AppealSubmitted':
                $this->logAppealSubmitted($event);
                break;

            case 'AppealReviewed':
                $this->logAppealReviewed($event);
                break;

            default:
                $this->logGenericEvent($event, $eventName);
                break;
        }
    }

    /**
     * Log report submitted event.
     */
    protected function logReportSubmitted(object $event): void
    {
        AuditLogService::log(
            'report_submitted',
            'New report submitted by user',
            $event->report->id, // entity_id
            'Report', // entity_type
            $event->report->reporter_id, // actor_id
            [
                'report_id' => $event->report->id,
                'reportable_type' => $event->report->reportable_type,
                'reportable_id' => $event->report->reportable_id,
                'category_id' => $event->report->category_id,
                'priority' => $event->report->priority,
                'evidence_count' => $event->report->evidence->count(),
            ]
        );
    }

    /**
     * Log report resolved event.
     */
    protected function logReportResolved(object $event): void
    {
        AuditLogService::log(
            'report_resolved',
            'Report resolved by moderator',
            $event->report->id, // entity_id
            'Report', // entity_type
            $event->moderator->id, // actor_id
            [
                'report_id' => $event->report->id,
                'resolution' => $event->report->status,
                'moderator_id' => $event->moderator->id,
                'resolution_notes' => $event->report->resolution_notes,
                'actions_taken' => $event->actionsTaken ?? [],
            ]
        );
    }

    /**
     * Log warning issued event.
     */
    protected function logWarningIssued(object $event): void
    {
        AuditLogService::log(
            'warning_issued',
            'Warning issued to user',
            $event->warning->id, // entity_id
            'Warning', // entity_type
            $event->warning->moderator_id, // actor_id
            [
                'warning_id' => $event->warning->id,
                'user_id' => $event->warning->user_id,
                'moderator_id' => $event->warning->moderator_id,
                'level' => $event->warning->level,
                'type' => $event->warning->type,
                'reason' => $event->warning->reason,
                'expires_at' => $event->warning->expires_at?->toISOString(),
            ]
        );
    }

    /**
     * Log restriction applied event.
     */
    protected function logRestrictionApplied(object $event): void
    {
        AuditLogService::log(
            'restriction_applied',
            'User restriction applied',
            $event->restriction->id, // entity_id
            'UserRestriction', // entity_type
            $event->restriction->moderator_id, // actor_id
            [
                'restriction_id' => $event->restriction->id,
                'user_id' => $event->restriction->user_id,
                'moderator_id' => $event->restriction->moderator_id,
                'type' => $event->restriction->type,
                'reason' => $event->restriction->reason,
                'expires_at' => $event->restriction->expires_at?->toISOString(),
                'is_permanent' => $event->restriction->is_permanent,
            ]
        );
    }

    /**
     * Log appeal submitted event.
     */
    protected function logAppealSubmitted(object $event): void
    {
        AuditLogService::log(
            'appeal_submitted',
            'User submitted appeal',
            $event->appeal->id, // entity_id
            'Appeal', // entity_type
            $event->appeal->user_id, // actor_id
            [
                'appeal_id' => $event->appeal->id,
                'appealable_type' => $event->appeal->appealable_type,
                'appealable_id' => $event->appeal->appealable_id,
                'user_id' => $event->appeal->user_id,
                'reason' => $event->appeal->reason,
                'evidence_count' => $event->appeal->evidence->count(),
            ]
        );
    }

    /**
     * Log appeal reviewed event.
     */
    protected function logAppealReviewed(object $event): void
    {
        AuditLogService::log(
            'appeal_reviewed',
            'Appeal reviewed by moderator',
            $event->appeal->id, // entity_id
            'Appeal', // entity_type
            $event->reviewer->id, // actor_id
            [
                'appeal_id' => $event->appeal->id,
                'reviewer_id' => $event->reviewer->id,
                'decision' => $event->appeal->status,
                'review_notes' => $event->appeal->review_notes,
                'original_decision' => $event->appeal->appealable_type,
            ]
        );
    }

    /**
     * Log generic event for unknown event types.
     */
    protected function logGenericEvent(object $event, string $eventName): void
    {
        AuditLogService::log(
            'moderation_event',
            "Moderation event: {$eventName}",
            null, // entity_id
            null, // entity_type
            Auth::id(), // actor_id
            [
                'event_class' => get_class($event),
                'event_data' => $this->extractEventData($event),
            ]
        );
    }

    /**
     * Extract event data for logging.
     */
    protected function extractEventData(object $event): array
    {
        $data = [];
        
        // Try to extract common properties
        $properties = ['id', 'user_id', 'moderator_id', 'report_id', 'warning_id', 'restriction_id', 'appeal_id'];
        
        foreach ($properties as $property) {
            if (property_exists($event, $property)) {
                $data[$property] = $event->$property;
            }
        }
        
        // Try to extract from nested objects
        $nestedProperties = ['report', 'warning', 'restriction', 'appeal', 'user', 'moderator'];
        
        foreach ($nestedProperties as $property) {
            if (property_exists($event, $property) && $event->$property) {
                $data[$property . '_id'] = $event->$property->id ?? null;
                $data[$property . '_type'] = class_basename($event->$property);
            }
        }
        
        return $data;
    }
}