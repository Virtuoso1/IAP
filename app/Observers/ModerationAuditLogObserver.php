<?php

namespace App\Observers;

use App\Models\ModerationAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ModerationAuditLogObserver
{
    /**
     * Handle audit log "created" event.
     */
    public function created(ModerationAuditLog $auditLog): void
    {
        $this->verifyIntegrity($auditLog);
        $this->logSecurityEvent($auditLog);
    }

    /**
     * Handle audit log "updated" event.
     */
    public function updated(ModerationAuditLog $auditLog): void
    {
        $this->verifyIntegrity($auditLog);
        $this->logSecurityEvent($auditLog);
    }

    /**
     * Handle audit log "deleted" event.
     */
    public function deleted(ModerationAuditLog $auditLog): void
    {
        $this->logSecurityEvent($auditLog, 'audit_log_deleted');
    }

    /**
     * Handle audit log "restored" event.
     */
    public function restored(ModerationAuditLog $auditLog): void
    {
        $this->verifyIntegrity($auditLog);
        $this->logSecurityEvent($auditLog, 'audit_log_restored');
    }

    /**
     * Verify audit log integrity.
     */
    protected function verifyIntegrity(ModerationAuditLog $auditLog): void
    {
        try {
            $data = $auditLog->toArray();
            $hash = $auditLog->hash;
            
            // Recalculate hash to verify integrity
            $calculatedHash = $this->calculateHash($data);
            
            if ($hash !== $calculatedHash) {
                Log::critical('Audit log integrity violation detected', [
                    'audit_log_id' => $auditLog->id,
                    'stored_hash' => $hash,
                    'calculated_hash' => $calculatedHash,
                    'action' => $auditLog->action,
                    'entity_type' => $auditLog->entity_type,
                    'entity_id' => $auditLog->entity_id
                ]);

                // Create security alert
                $this->createSecurityAlert('audit_log_integrity_violation', [
                    'audit_log_id' => $auditLog->id,
                    'stored_hash' => $hash,
                    'calculated_hash' => $calculatedHash
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to verify audit log integrity", [
                'error' => $e->getMessage(),
                'audit_log_id' => $auditLog->id
            ]);
        }
    }

    /**
     * Log security events.
     */
    protected function logSecurityEvent(ModerationAuditLog $auditLog, string $eventType = null): void
    {
        try {
            $eventType = $eventType ?: $auditLog->action;
            
            // Check for suspicious patterns
            $this->checkSuspiciousPatterns($auditLog);
            
            // Log high-priority actions
            if (in_array($auditLog->action, ['user_banned', 'content_deleted', 'mass_action'])) {
                Log::warning('High-priority moderation action', [
                    'audit_log_id' => $auditLog->id,
                    'action' => $auditLog->action,
                    'entity_type' => $auditLog->entity_type,
                    'entity_id' => $auditLog->entity_id,
                    'actor_id' => $auditLog->actor_id,
                    'ip_address' => $auditLog->ip_address
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to log security event", [
                'error' => $e->getMessage(),
                'audit_log_id' => $auditLog->id,
                'event_type' => $eventType
            ]);
        }
    }

    /**
     * Check for suspicious patterns.
     */
    protected function checkSuspiciousPatterns(ModerationAuditLog $auditLog): void
    {
        try {
            // Check for rapid actions from same IP
            $recentLogs = ModerationAuditLog::where('ip_address', $auditLog->ip_address)
                ->where('created_at', '>', now()->subMinutes(5))
                ->where('id', '!=', $auditLog->id)
                ->count();

            if ($recentLogs > 10) {
                $this->createSecurityAlert('rapid_actions_from_ip', [
                    'ip_address' => $auditLog->ip_address,
                    'action_count' => $recentLogs + 1,
                    'timeframe' => '5 minutes'
                ]);
            }

            // Check for actions outside normal hours
            $hour = now()->hour;
            if ($hour < 6 || $hour > 22) {
                if (in_array($auditLog->action, ['user_banned', 'content_deleted', 'mass_action'])) {
                    $this->createSecurityAlert('suspicious_after_hours_action', [
                        'audit_log_id' => $auditLog->id,
                        'action' => $auditLog->action,
                        'hour' => $hour,
                        'actor_id' => $auditLog->actor_id
                    ]);
                }
            }

            // Check for multiple failed login attempts
            if ($auditLog->action === 'login_failed') {
                $failedAttempts = ModerationAuditLog::where('actor_id', $auditLog->actor_id)
                    ->where('action', 'login_failed')
                    ->where('created_at', '>', now()->subMinutes(15))
                    ->count();

                if ($failedAttempts >= 5) {
                    $this->createSecurityAlert('multiple_failed_logins', [
                        'actor_id' => $auditLog->actor_id,
                        'failed_attempts' => $failedAttempts,
                        'timeframe' => '15 minutes'
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("Failed to check suspicious patterns", [
                'error' => $e->getMessage(),
                'audit_log_id' => $auditLog->id
            ]);
        }
    }

    /**
     * Create security alert.
     */
    protected function createSecurityAlert(string $alertType, array $metadata): void
    {
        try {
            $actorId = null;
            if (Auth::check()) {
                $actorId = Auth::id();
            }

            // Create security alert - fallback to audit log if SecurityAlert model doesn't exist
            try {
                if (class_exists('\App\Models\SecurityAlert')) {
                    \App\Models\SecurityAlert::create([
                        'type' => $alertType,
                        'severity' => $this->getAlertSeverity($alertType),
                        'description' => $this->getAlertDescription($alertType, $metadata),
                        'metadata' => $metadata,
                        'actor_id' => $actorId,
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'created_at' => now()
                    ]);
                } else {
                    // Fallback: create audit log entry for security alert
                    ModerationAuditLog::create([
                        'entity_type' => 'SecurityAlert',
                        'entity_id' => 0,
                        'action' => $alertType,
                        'old_values' => null,
                        'new_values' => [
                            'type' => $alertType,
                            'severity' => $this->getAlertSeverity($alertType),
                            'description' => $this->getAlertDescription($alertType, $metadata),
                            'metadata' => $metadata
                        ],
                        'actor_id' => $actorId,
                        'actor_type' => 'system',
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'created_at' => now()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to create security alert", [
                    'error' => $e->getMessage(),
                    'alert_type' => $alertType,
                    'metadata' => $metadata
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to create security alert", [
                'error' => $e->getMessage(),
                'alert_type' => $alertType,
                'metadata' => $metadata
            ]);
        }
    }

    /**
     * Get alert severity.
     */
    protected function getAlertSeverity(string $alertType): string
    {
        $severities = [
            'audit_log_integrity_violation' => 'critical',
            'rapid_actions_from_ip' => 'high',
            'suspicious_after_hours_action' => 'medium',
            'multiple_failed_logins' => 'high',
            'unauthorized_access_attempt' => 'critical',
            'data_tampering_detected' => 'critical'
        ];

        return $severities[$alertType] ?? 'medium';
    }

    /**
     * Get alert description.
     */
    protected function getAlertDescription(string $alertType, array $metadata): string
    {
        $descriptions = [
            'audit_log_integrity_violation' => "Audit log integrity violation detected for log ID: {$metadata['audit_log_id']}",
            'rapid_actions_from_ip' => "Rapid actions detected from IP: {$metadata['ip_address']} ({$metadata['action_count']} actions in {$metadata['timeframe']})",
            'suspicious_after_hours_action' => "Suspicious action '{$metadata['action']}' performed outside normal hours by user ID: {$metadata['actor_id']}",
            'multiple_failed_logins' => "Multiple failed login attempts detected for user ID: {$metadata['actor_id']} ({$metadata['failed_attempts']} attempts in {$metadata['timeframe']})",
            'unauthorized_access_attempt' => "Unauthorized access attempt detected",
            'data_tampering_detected' => "Data tampering detected"
        ];

        return $descriptions[$alertType] ?? "Security alert: {$alertType}";
    }

    /**
     * Calculate hash for integrity verification.
     */
    protected function calculateHash(array $data): string
    {
        unset($data['hash']); // Remove hash from calculation
        unset($data['created_at']); // Remove timestamp from calculation
        unset($data['updated_at']); // Remove timestamp from calculation
        
        $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES);
        return hash('sha256', $jsonData . config('app.audit_hash_secret', 'default-secret'));
    }

    /**
     * Handle audit log cleanup.
     */
    public function cleanupOldLogs(int $daysOld = 365): int
    {
        try {
            $cutoffDate = now()->subDays($daysOld);
            $oldLogs = ModerationAuditLog::where('created_at', '<', $cutoffDate)->get();
            $deletedCount = 0;
            
            foreach ($oldLogs as $log) {
                // Archive before deleting
                $this->archiveLog($log);
                $log->delete();
                $deletedCount++;
            }
            
            Log::info("Old audit logs cleaned up", [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate
            ]);
            
            return $deletedCount;
        } catch (\Exception $e) {
            Log::error("Failed to cleanup old audit logs", [
                'error' => $e->getMessage(),
                'days_old' => $daysOld
            ]);
            return 0;
        }
    }

    /**
     * Archive audit log.
     */
    protected function archiveLog(ModerationAuditLog $log): void
    {
        try {
            $archiveData = $log->toArray();
            $archiveData['archived_at'] = now()->toISOString();
            
            // Store in archive storage
            $archivePath = 'audit_logs/' . date('Y/m/d') . "/audit_log_{$log->id}.json";
            Storage::disk('archive')->put($archivePath, json_encode($archiveData, JSON_PRETTY_PRINT));
            
        } catch (\Exception $e) {
            Log::error("Failed to archive audit log", [
                'error' => $e->getMessage(),
                'audit_log_id' => $log->id
            ]);
        }
    }
}