<?php

namespace App\Observers;

use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReportObserver
{
    /**
     * Handle the report "created" event.
     */
    public function created(Report $report): void
    {
        $this->logAuditTrail('report_created', $report);
    }

    /**
     * Handle the report "updated" event.
     */
    public function updated(Report $report): void
    {
        $this->logAuditTrail('report_updated', $report);
    }

    /**
     * Handle the report "deleted" event.
     */
    public function deleted(Report $report): void
    {
        $this->logAuditTrail('report_deleted', $report);
    }

    /**
     * Handle the report "restored" event.
     */
    public function restored(Report $report): void
    {
        $this->logAuditTrail('report_restored', $report);
    }

    /**
     * Handle the report "force deleted" event.
     */
    public function forceDeleted(Report $report): void
    {
        $this->logAuditTrail('report_force_deleted', $report);
    }

    /**
     * Log audit trail for report actions.
     */
    protected function logAuditTrail(string $action, Report $report): void
    {
        try {
            $actorId = null;
            $actorType = 'system';
            
            if (Auth::check()) {
                $actorId = Auth::id();
                $actorType = 'user';
            }

            $auditData = [
                'entity_type' => 'Report',
                'entity_id' => $report->id,
                'action' => $action,
                'old_values' => $report->getOriginal(),
                'new_values' => $report->toArray(),
                'actor_id' => $actorId,
                'actor_type' => $actorType,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ];

            // Add specific actor information based on action
            switch ($action) {
                case 'report_updated':
                    if ($actorId) {
                        $auditData['metadata']['updated_by'] = $actorId;
                    }
                    break;
                case 'report_deleted':
                    if ($actorId) {
                        $auditData['metadata']['deleted_by'] = $actorId;
                    }
                    break;
                case 'report_restored':
                    if ($actorId) {
                        $auditData['metadata']['restored_by'] = $actorId;
                    }
                    break;
                case 'report_force_deleted':
                    if ($actorId) {
                        $auditData['metadata']['deleted_by'] = $actorId;
                    }
                    break;
            }

            // Create audit log entry
            \App\Models\ModerationAuditLog::create($auditData);

            // Log status changes
            if ($action === 'report_updated' && isset($report->getOriginal()['status'])) {
                $oldStatus = $report->getOriginal()['status'];
                $newStatus = $report->status;
                
                if ($oldStatus !== $newStatus) {
                    Log::info('Report status changed', [
                        'report_id' => $report->id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'changed_by' => $actorId
                    ]);
                }
            }

            // Log priority changes
            if ($action === 'report_updated' && isset($report->getOriginal()['priority'])) {
                $oldPriority = $report->getOriginal()['priority'];
                $newPriority = $report->priority;
                
                if ($oldPriority !== $newPriority) {
                    Log::info('Report priority changed', [
                        'report_id' => $report->id,
                        'old_priority' => $oldPriority,
                        'new_priority' => $newPriority,
                        'changed_by' => $actorId
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("Failed to log audit trail for report action: {$action}", [
                'error' => $e->getMessage(),
                'report_id' => $report->id,
                'action' => $action
            ]);
        }
    }

    /**
     * Handle report assignment.
     */
    public function reportAssigned(Report $report, int $moderatorId): void
    {
        try {
            $report->update([
                'moderator_id' => $moderatorId,
                'assigned_at' => now()
            ]);

            $this->logAuditTrail('report_assigned', $report);

            Log::info('Report assigned to moderator', [
                'report_id' => $report->id,
                'moderator_id' => $moderatorId,
                'assigned_by' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to assign report", [
                'error' => $e->getMessage(),
                'report_id' => $report->id,
                'moderator_id' => $moderatorId
            ]);
        }
    }

    /**
     * Handle report escalation.
     */
    public function reportEscalated(Report $report, string $escalationReason): void
    {
        try {
            $report->update([
                'escalated_at' => now(),
                'escalation_reason' => $escalationReason,
                'priority' => 'critical'
            ]);

            $this->logAuditTrail('report_escalated', $report);

            Log::warning('Report escalated', [
                'report_id' => $report->id,
                'escalation_reason' => $escalationReason,
                'escalated_by' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to escalate report", [
                'error' => $e->getMessage(),
                'report_id' => $report->id,
                'escalation_reason' => $escalationReason
            ]);
        }
    }

    /**
     * Handle report resolution.
     */
    public function reportResolved(Report $report, string $resolution): void
    {
        try {
            $report->update([
                'status' => 'resolved',
                'resolution' => $resolution,
                'resolved_at' => now(),
                'moderator_id' => Auth::id()
            ]);

            $this->logAuditTrail('report_resolved', $report);

            Log::info('Report resolved', [
                'report_id' => $report->id,
                'resolution' => $resolution,
                'resolved_by' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to resolve report", [
                'error' => $e->getMessage(),
                'report_id' => $report->id,
                'resolution' => $resolution
            ]);
        }
    }

    /**
     * Handle report dismissal.
     */
    public function reportDismissed(Report $report, string $dismissalReason): void
    {
        try {
            $report->update([
                'status' => 'dismissed',
                'resolution' => $dismissalReason,
                'resolved_at' => now(),
                'moderator_id' => Auth::id()
            ]);

            $this->logAuditTrail('report_dismissed', $report);

            Log::info('Report dismissed', [
                'report_id' => $report->id,
                'dismissal_reason' => $dismissalReason,
                'dismissed_by' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to dismiss report", [
                'error' => $e->getMessage(),
                'report_id' => $report->id,
                'dismissal_reason' => $dismissalReason
            ]);
        }
    }
}