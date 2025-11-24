<?php

namespace App\Services;

use App\Models\ModerationAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Log an audit entry.
     *
     * @param string $action
     * @param string $description
     * @param array $metadata
     * @param int|null $actorId
     * @param string|null $entityType
     * @param int|null $entityId
     * @return ModerationAuditLog
     */
    public static function log(
        string $action,
        ?string $description = null,
        ?int $entityId = null,
        ?string $entityType = null,
        ?int $actorId = null,
        array $metadata = []
    ): ModerationAuditLog {
        $actorId = $actorId ?? Auth::id();
        $ipAddress = Request::ip();
        $userAgent = Request::userAgent();

        $auditData = [
            'action' => $action,
            'description' => $description,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'actor_id' => $actorId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'metadata' => $metadata,
        ];

        // Generate hash for integrity
        $hash = hash('sha256', json_encode($auditData, 64)); // 64 = JSON_SORT_KEYS
        $auditData['log_hash'] = $hash;

        return ModerationAuditLog::create($auditData);
    }

    /**
     * Log a report-related action.
     */
    public static function logReportAction(
        string $action,
        int $reportId,
        string $description,
        array $metadata = [],
        ?int $actorId = null
    ): ModerationAuditLog {
        return self::log(
            $action,
            $description,
            $reportId,
            'Report',
            $actorId,
            $metadata
        );
    }

    /**
     * Log a warning-related action.
     */
    public static function logWarningAction(
        string $action,
        int $warningId,
        string $description,
        array $metadata = [],
        ?int $actorId = null
    ): ModerationAuditLog {
        return self::log(
            $action,
            $description,
            $warningId,
            'Warning',
            $actorId,
            $metadata
        );
    }

    /**
     * Log a restriction-related action.
     */
    public static function logRestrictionAction(
        string $action,
        int $restrictionId,
        string $description,
        array $metadata = [],
        ?int $actorId = null
    ): ModerationAuditLog {
        return self::log(
            $action,
            $description,
            $restrictionId,
            'UserRestriction',
            $actorId,
            $metadata
        );
    }

    /**
     * Log an appeal-related action.
     */
    public static function logAppealAction(
        string $action,
        int $appealId,
        string $description,
        array $metadata = [],
        ?int $actorId = null
    ): ModerationAuditLog {
        return self::log(
            $action,
            $description,
            $appealId,
            'Appeal',
            $actorId,
            $metadata
        );
    }

    /**
     * Log a user-related action.
     */
    public static function logUserAction(
        string $action,
        int $userId,
        string $description,
        array $metadata = [],
        ?int $actorId = null
    ): ModerationAuditLog {
        return self::log(
            $action,
            $description,
            $userId,
            'User',
            $actorId,
            $metadata
        );
    }

    /**
     * Verify audit log integrity.
     */
    public static function verifyIntegrity(ModerationAuditLog $auditLog): bool
    {
        $data = $auditLog->toArray();
        unset($data['hash']);
        
        $calculatedHash = hash('sha256', json_encode($data, 64)); // 64 = JSON_SORT_KEYS
        return hash_equals($auditLog->log_hash ?? '', $calculatedHash);
    }

    /**
     * Get audit logs for a specific entity.
     */
    public static function getEntityLogs(string $entityType, int $entityId, int $limit = 50)
    {
        return ModerationAuditLog::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->with('actor')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs for a specific actor.
     */
    public static function getActorLogs(int $actorId, int $limit = 50)
    {
        return ModerationAuditLog::where('actor_id', $actorId)
            ->with(['entity'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search audit logs.
     */
    public static function searchLogs(array $filters = [], int $limit = 100)
    {
        $query = ModerationAuditLog::with(['actor', 'entity']);

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['actor_id'])) {
            $query->where('actor_id', $filters['actor_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Verify integrity of audit logs.
     */
    public static function verifyBatchIntegrity(?int $limit = null): array
    {
        $startTime = microtime(true);
        $query = ModerationAuditLog::orderBy('created_at', 'desc');
        
        if ($limit) {
            $query->limit($limit);
        }

        $auditLogs = $query->get();
        $totalLogs = $auditLogs->count();
        $violations = 0;
        $lastVerifiedHash = null;

        foreach ($auditLogs as $log) {
            if (!self::verifyIntegrity($log)) {
                $violations++;
            }
            $lastVerifiedHash = $log->hash;
        }

        $endTime = microtime(true);
        $durationMs = round(($endTime - $startTime) * 1000, 2);
        $integrityScore = $totalLogs > 0 ? round((($totalLogs - $violations) / $totalLogs) * 100, 2) : 100;

        return [
            'verification_passed' => $violations === 0,
            'total_logs_checked' => $totalLogs,
            'violations_found' => $violations,
            'integrity_score' => $integrityScore,
            'last_verified_hash' => $lastVerifiedHash,
            'checked_at' => now()->toISOString(),
            'check_duration_ms' => $durationMs,
        ];
    }
}