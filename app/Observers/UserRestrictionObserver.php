<?php

namespace App\Observers;

use App\Models\UserRestriction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserRestrictionObserver
{
    /**
     * Handle user restriction "created" event.
     */
    public function created(UserRestriction $restriction): void
    {
        $this->logAuditTrail('restriction_created', $restriction);
    }

    /**
     * Handle user restriction "updated" event.
     */
    public function updated(UserRestriction $restriction): void
    {
        $this->logAuditTrail('restriction_updated', $restriction);
    }

    /**
     * Handle user restriction "deleted" event.
     */
    public function deleted(UserRestriction $restriction): void
    {
        $this->logAuditTrail('restriction_deleted', $restriction);
    }

    /**
     * Handle user restriction "restored" event.
     */
    public function restored(UserRestriction $restriction): void
    {
        $this->logAuditTrail('restriction_restored', $restriction);
    }

    /**
     * Handle user restriction "force deleted" event.
     */
    public function forceDeleted(UserRestriction $restriction): void
    {
        $this->logAuditTrail('restriction_force_deleted', $restriction);
    }

    /**
     * Log audit trail for restriction actions.
     */
    protected function logAuditTrail(string $action, UserRestriction $restriction): void
    {
        try {
            $actorId = null;
            $actorType = 'system';
            
            if (Auth::check()) {
                $actorId = Auth::id();
                $actorType = 'user';
            }

            $auditData = [
                'entity_type' => 'UserRestriction',
                'entity_id' => $restriction->id,
                'action' => $action,
                'old_values' => $restriction->getOriginal(),
                'new_values' => $restriction->toArray(),
                'actor_id' => $actorId,
                'actor_type' => $actorType,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ];

            // Add specific actor information based on action
            switch ($action) {
                case 'restriction_updated':
                    if ($actorId) {
                        $auditData['metadata']['updated_by'] = $actorId;
                    }
                    break;
                case 'restriction_deleted':
                    if ($actorId) {
                        $auditData['metadata']['deleted_by'] = $actorId;
                    }
                    break;
                case 'restriction_restored':
                    if ($actorId) {
                        $auditData['metadata']['restored_by'] = $actorId;
                    }
                    break;
                case 'restriction_force_deleted':
                    if ($actorId) {
                        $auditData['metadata']['deleted_by'] = $actorId;
                    }
                    break;
            }

            // Create audit log entry
            \App\Models\ModerationAuditLog::create($auditData);

            // Log restriction type changes
            if ($action === 'restriction_updated' && isset($restriction->getOriginal()['type'])) {
                $oldType = $restriction->getOriginal()['type'];
                $newType = $restriction->type;
                
                if ($oldType !== $newType) {
                    Log::info('Restriction type changed', [
                        'restriction_id' => $restriction->id,
                        'user_id' => $restriction->user_id,
                        'old_type' => $oldType,
                        'new_type' => $newType,
                        'changed_by' => $actorId
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("Failed to log audit trail for restriction action: {$action}", [
                'error' => $e->getMessage(),
                'restriction_id' => $restriction->id,
                'action' => $action
            ]);
        }
    }

    /**
     * Handle restriction expiration.
     */
    public function restrictionExpired(UserRestriction $restriction): void
    {
        try {
            $restriction->update([
                'is_active' => false,
                'expired_at' => now()
            ]);

            $this->logAuditTrail('restriction_expired', $restriction);

            Log::info('User restriction expired automatically', [
                'restriction_id' => $restriction->id,
                'user_id' => $restriction->user_id,
                'type' => $restriction->type
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to process restriction expiration", [
                'error' => $e->getMessage(),
                'restriction_id' => $restriction->id
            ]);
        }
    }

    /**
     * Handle restriction lift.
     */
    public function restrictionLifted(UserRestriction $restriction, string $liftReason): void
    {
        try {
            $restriction->update([
                'is_active' => false,
                'lifted_at' => now(),
                'lift_reason' => $liftReason,
                'lifted_by' => Auth::id()
            ]);

            $this->logAuditTrail('restriction_lifted', $restriction);

            Log::info('User restriction lifted', [
                'restriction_id' => $restriction->id,
                'user_id' => $restriction->user_id,
                'type' => $restriction->type,
                'lift_reason' => $liftReason,
                'lifted_by' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to lift restriction", [
                'error' => $e->getMessage(),
                'restriction_id' => $restriction->id,
                'lift_reason' => $liftReason
            ]);
        }
    }

    /**
     * Handle restriction extension.
     */
    public function restrictionExtended(UserRestriction $restriction, int $extensionDays, string $extensionReason): void
    {
        try {
            $newExpiresAt = $restriction->expires_at ? $restriction->expires_at->addDays($extensionDays) : now()->addDays($extensionDays);
            
            $restriction->update([
                'expires_at' => $newExpiresAt,
                'extended_at' => now(),
                'extension_reason' => $extensionReason,
                'extended_by' => Auth::id()
            ]);

            $this->logAuditTrail('restriction_extended', $restriction);

            Log::warning('User restriction extended', [
                'restriction_id' => $restriction->id,
                'user_id' => $restriction->user_id,
                'type' => $restriction->type,
                'extension_days' => $extensionDays,
                'extension_reason' => $extensionReason,
                'new_expires_at' => $newExpiresAt,
                'extended_by' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to extend restriction", [
                'error' => $e->getMessage(),
                'restriction_id' => $restriction->id,
                'extension_days' => $extensionDays
            ]);
        }
    }

    /**
     * Handle restriction escalation.
     */
    public function restrictionEscalated(UserRestriction $restriction, string $escalationReason): void
    {
        try {
            $restriction->update([
                'escalated_at' => now(),
                'escalation_reason' => $escalationReason
            ]);

            $this->logAuditTrail('restriction_escalated', $restriction);

            Log::warning('User restriction escalated', [
                'restriction_id' => $restriction->id,
                'user_id' => $restriction->user_id,
                'type' => $restriction->type,
                'escalation_reason' => $escalationReason
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to escalate restriction", [
                'error' => $e->getMessage(),
                'restriction_id' => $restriction->id,
                'escalation_reason' => $escalationReason
            ]);
        }
    }
}