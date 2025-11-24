<?php

namespace App\Observers;

use App\Models\Warning;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WarningObserver
{
    /**
     * Handle the warning "created" event.
     */
    public function created(Warning $warning): void
    {
        $this->logAuditTrail('warning_created', $warning);
    }

    /**
     * Handle the warning "updated" event.
     */
    public function updated(Warning $warning): void
    {
        $this->logAuditTrail('warning_updated', $warning);
    }

    /**
     * Handle the warning "deleted" event.
     */
    public function deleted(Warning $warning): void
    {
        $this->logAuditTrail('warning_deleted', $warning);
    }

    /**
     * Handle the warning "restored" event.
     */
    public function restored(Warning $warning): void
    {
        $this->logAuditTrail('warning_restored', $warning);
    }

    /**
     * Handle the warning "force deleted" event.
     */
    public function forceDeleted(Warning $warning): void
    {
        $this->logAuditTrail('warning_force_deleted', $warning);
    }

    /**
     * Log audit trail for warning actions.
     */
    protected function logAuditTrail(string $action, Warning $warning): void
    {
        try {
            $actorId = null;
            $actorType = 'system';
            
            if (Auth::check()) {
                $actorId = Auth::id();
                $actorType = 'user';
            }

            $auditData = [
                'entity_type' => 'Warning',
                'entity_id' => $warning->id,
                'action' => $action,
                'old_values' => $warning->getOriginal(),
                'new_values' => $warning->toArray(),
                'actor_id' => $actorId,
                'actor_type' => $actorType,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ];

            // Add specific actor information based on action
            switch ($action) {
                case 'warning_updated':
                    if ($actorId) {
                        $auditData['metadata']['updated_by'] = $actorId;
                    }
                    break;
                case 'warning_deleted':
                    if ($actorId) {
                        $auditData['metadata']['deleted_by'] = $actorId;
                    }
                    break;
                case 'warning_restored':
                    if ($actorId) {
                        $auditData['metadata']['restored_by'] = $actorId;
                    }
                    break;
                case 'warning_force_deleted':
                    if ($actorId) {
                        $auditData['metadata']['deleted_by'] = $actorId;
                    }
                    break;
            }

            // Create audit log entry
            \App\Models\ModerationAuditLog::create($auditData);

            // Log warning level changes
            if ($action === 'warning_updated' && isset($warning->getOriginal()['level'])) {
                $oldLevel = $warning->getOriginal()['level'];
                $newLevel = $warning->level;
                
                if ($oldLevel !== $newLevel) {
                    Log::info('Warning level changed', [
                        'warning_id' => $warning->id,
                        'user_id' => $warning->user_id,
                        'old_level' => $oldLevel,
                        'new_level' => $newLevel,
                        'changed_by' => $actorId
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("Failed to log audit trail for warning action: {$action}", [
                'error' => $e->getMessage(),
                'warning_id' => $warning->id,
                'action' => $action
            ]);
        }
    }

    /**
     * Handle warning expiration.
     */
    public function warningExpired(Warning $warning): void
    {
        try {
            $warning->update([
                'status' => 'expired',
                'expired_at' => now()
            ]);

            $this->logAuditTrail('warning_expired', $warning);

            Log::info('Warning expired automatically', [
                'warning_id' => $warning->id,
                'user_id' => $warning->user_id,
                'level' => $warning->level
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to process warning expiration", [
                'error' => $e->getMessage(),
                'warning_id' => $warning->id
            ]);
        }
    }

    /**
     * Handle warning escalation.
     */
    public function warningEscalated(Warning $warning, string $reason): void
    {
        try {
            $warning->update([
                'escalated_at' => now(),
                'escalation_reason' => $reason
            ]);

            $this->logAuditTrail('warning_escalated', $warning);

            Log::warning('Warning escalated', [
                'warning_id' => $warning->id,
                'user_id' => $warning->user_id,
                'level' => $warning->level,
                'reason' => $reason
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to process warning escalation", [
                'error' => $e->getMessage(),
                'warning_id' => $warning->id,
                'reason' => $reason
            ]);
        }
    }
}