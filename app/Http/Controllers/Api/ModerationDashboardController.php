<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Warning;
use App\Models\UserRestriction;
use App\Models\Appeal;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModerationDashboardController extends Controller
{
    /**
     * Get dashboard overview statistics.
     */
    public function overview(Request $request): JsonResponse
    {
        $this->authorize('viewDashboard', Report::class);

        try {
            $dateFrom = $request->date_from ?? now()->subDays(30);
            $dateTo = $request->date_to ?? now();

            $overview = [
                'summary' => $this->getSummaryStats($dateFrom, $dateTo),
                'reports' => $this->getReportStats($dateFrom, $dateTo),
                'warnings' => $this->getWarningStats($dateFrom, $dateTo),
                'restrictions' => $this->getRestrictionStats($dateFrom, $dateTo),
                'appeals' => $this->getAppealStats($dateFrom, $dateTo),
                'performance' => $this->getPerformanceMetrics($dateFrom, $dateTo),
            ];

            return response()->json([
                'success' => true,
                'data' => $overview,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load dashboard overview', [
                'error' => $e->getMessage(),
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard overview',
                'error_code' => 'dashboard_load_failed',
            ], 500);
        }
    }

    /**
     * Get recent moderation activity.
     */
    public function activity(Request $request): JsonResponse
    {
        $this->authorize('viewDashboard', Report::class);

        try {
            $activities = $this->getRecentActivity($request);

            return response()->json([
                'success' => true,
                'data' => $activities,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load moderation activity', [
                'error' => $e->getMessage(),
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load moderation activity',
                'error_code' => 'activity_load_failed',
            ], 500);
        }
    }

    /**
     * Get moderator performance metrics.
     */
    public function performance(Request $request): JsonResponse
    {
        $this->authorize('viewDashboard', Report::class);

        try {
            $dateFrom = $request->date_from ?? now()->subDays(30);
            $dateTo = $request->date_to ?? now();

            $performance = $this->getPerformanceMetrics($dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'data' => $performance,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load performance metrics', [
                'error' => $e->getMessage(),
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load performance metrics',
                'error_code' => 'performance_load_failed',
            ], 500);
        }
    }

    /**
     * Get moderation queue status.
     */
    public function queue(Request $request): JsonResponse
    {
        $this->authorize('viewDashboard', Report::class);

        try {
            $queueStatus = $this->getQueueStatus();

            return response()->json([
                'success' => true,
                'data' => $queueStatus,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load queue status', [
                'error' => $e->getMessage(),
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load queue status',
                'error_code' => 'queue_load_failed',
            ], 500);
        }
    }

    /**
     * Get moderation alerts.
     */
    public function alerts(Request $request): JsonResponse
    {
        $this->authorize('viewDashboard', Report::class);

        try {
            $alerts = $this->getActiveAlerts();

            return response()->json([
                'success' => true,
                'data' => $alerts,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load moderation alerts', [
                'error' => $e->getMessage(),
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load moderation alerts',
                'error_code' => 'alerts_load_failed',
            ], 500);
        }
    }

    /**
     * Get audit logs.
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $this->authorize('viewAuditLogs', Report::class);

        try {
            $logs = $this->getAuditLogs($request);

            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load audit logs', [
                'error' => $e->getMessage(),
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load audit logs',
                'error_code' => 'audit_logs_load_failed',
            ], 500);
        }
    }

    /**
     * Get integrity check status.
     */
    public function integrityCheck(Request $request): JsonResponse
    {
        $this->authorize('viewAuditLogs', Report::class);

        try {
            $integrityStatus = $this->getIntegrityStatus();

            return response()->json([
                'success' => true,
                'data' => $integrityStatus,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load integrity status', [
                'error' => $e->getMessage(),
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load integrity status',
                'error_code' => 'integrity_load_failed',
            ], 500);
        }
    }

    /**
     * Verify audit integrity manually.
     */
    public function verifyIntegrity(Request $request): JsonResponse
    {
        $this->authorize('verifyIntegrity', Report::class);

        $request->validate([
            'limit' => 'nullable|integer|min:1|max:10000',
        ]);

        try {
            $auditService = app(AuditLogService::class);
            $result = $auditService->verifyIntegrity($request->limit);

            return response()->json([
                'success' => true,
                'message' => 'Integrity verification completed',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to verify integrity', [
                'error' => $e->getMessage(),
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify integrity',
                'error_code' => 'integrity_verification_failed',
            ], 500);
        }
    }

    /**
     * Get summary statistics.
     */
    protected function getSummaryStats($dateFrom, $dateTo): array
    {
        return [
            'total_reports' => Report::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'pending_reports' => Report::where('status', 'pending')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'resolved_reports' => Report::where('status', 'resolved')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'active_warnings' => Warning::where('status', 'active')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'active_restrictions' => UserRestriction::where('is_active', true)->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'pending_appeals' => Appeal::where('status', 'pending')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'critical_reports_today' => Report::where('priority', 'critical')
                ->where('status', 'pending')
                ->whereDate('created_at', today())
                ->count(),
        ];
    }

    /**
     * Get report statistics.
     */
    protected function getReportStats($dateFrom, $dateTo): array
    {
        return [
            'by_status' => Report::selectRaw('status, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_priority' => Report::selectRaw('priority, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('priority')
                ->pluck('count', 'priority'),
            'by_category' => Report::with('category:name')
                ->selectRaw('category_id, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('category_id')
                ->get(),
            'average_resolution_time' => Report::whereNotNull('resolved_at')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                ->value('avg_hours'),
        ];
    }

    /**
     * Get warning statistics.
     */
    protected function getWarningStats($dateFrom, $dateTo): array
    {
        return [
            'by_level' => Warning::selectRaw('level, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('level')
                ->pluck('count', 'level'),
            'by_type' => Warning::selectRaw('type, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('type')
                ->pluck('count', 'type'),
            'escalated_warnings' => Warning::where('status', 'escalated')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
        ];
    }

    /**
     * Get restriction statistics.
     */
    protected function getRestrictionStats($dateFrom, $dateTo): array
    {
        return [
            'by_type' => UserRestriction::selectRaw('type, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('type')
                ->pluck('count', 'type'),
            'active_restrictions' => UserRestriction::where('is_active', true)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
            'permanent_restrictions' => UserRestriction::where('is_permanent', true)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
        ];
    }

    /**
     * Get appeal statistics.
     */
    protected function getAppealStats($dateFrom, $dateTo): array
    {
        return [
            'by_status' => Appeal::selectRaw('status, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_outcome' => Appeal::selectRaw('status, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('status')
                ->pluck('count', 'status'),
            'average_review_time' => Appeal::whereNotNull('reviewed_at')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at)) as avg_hours')
                ->value('avg_hours'),
        ];
    }

    /**
     * Get performance metrics.
     */
    protected function getPerformanceMetrics($dateFrom, $dateTo): array
    {
        $moderatorId = Auth::id();

        return [
            'reports_handled' => Report::where('moderator_id', $moderatorId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
            'average_resolution_time' => Report::where('moderator_id', $moderatorId)
                ->whereNotNull('resolved_at')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                ->value('avg_hours'),
            'warnings_issued' => Warning::where('moderator_id', $moderatorId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
            'restrictions_applied' => UserRestriction::where('moderator_id', $moderatorId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
            'appeals_reviewed' => Appeal::where('reviewer_id', $moderatorId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
            'efficiency_score' => $this->calculateEfficiencyScore($moderatorId, $dateFrom, $dateTo),
        ];
    }

    /**
     * Get recent activity.
     */
    protected function getRecentActivity(Request $request): array
    {
        $limit = $request->get('limit', 50);
        $moderatorId = Auth::id();

        $reports = Report::with(['reporter', 'category'])
            ->where('moderator_id', $moderatorId)
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();

        $warnings = Warning::with(['user', 'appeals'])
            ->where('moderator_id', $moderatorId)
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();

        $restrictions = UserRestriction::with('user')
            ->where('moderator_id', $moderatorId)
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();

        return [
            'reports' => $reports,
            'warnings' => $warnings,
            'restrictions' => $restrictions,
        ];
    }

    /**
     * Get queue status.
     */
    protected function getQueueStatus(): array
    {
        return [
            'pending_reports' => Report::where('status', 'pending')->count(),
            'critical_reports' => Report::where('priority', 'critical')
                ->where('status', 'pending')
                ->count(),
            'overdue_reports' => Report::where('status', 'pending')
                ->where('created_at', '<', now()->subHours(24))
                ->count(),
            'queue_size' => Cache::get('moderation_queue_size', 0),
            'average_processing_time' => Cache::get('moderation_avg_processing_time', 0),
        ];
    }

    /**
     * Get active alerts.
     */
    protected function getActiveAlerts(): array
    {
        $alerts = [];

        // Critical reports alert
        $criticalReports = Report::where('priority', 'critical')
            ->where('status', 'pending')
            ->count();

        if ($criticalReports > 0) {
            $alerts[] = [
                'type' => 'critical',
                'message' => "{$criticalReports} critical reports pending review",
                'severity' => 'high',
                'created_at' => now()->toISOString(),
            ];
        }

        // High queue size alert
        $queueSize = Cache::get('moderation_queue_size', 0);
        if ($queueSize > 100) {
            $alerts[] = [
                'type' => 'queue',
                'message' => "Moderation queue size: {$queueSize} (threshold: 100)",
                'severity' => 'medium',
                'created_at' => now()->toISOString(),
            ];
        }

        // Integrity check alert
        $lastIntegrityCheck = Cache::get('last_integrity_check');
        if (!$lastIntegrityCheck || now()->parse($lastIntegrityCheck)->diffInDays() > 7) {
            $alerts[] = [
                'type' => 'integrity',
                'message' => 'Audit integrity check overdue',
                'severity' => 'high',
                'created_at' => now()->toISOString(),
            ];
        }

        return $alerts;
    }

    /**
     * Get audit logs.
     */
    protected function getAuditLogs(Request $request): array
    {
        $limit = $request->get('limit', 100);
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        $query = DB::table('moderation_audit_logs')
            ->orderBy('timestamp', 'desc')
            ->limit($limit);

        if ($dateFrom) {
            $query->where('timestamp', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('timestamp', '<=', $dateTo);
        }

        return $query->get()->toArray();
    }

    /**
     * Get integrity status.
     */
    protected function getIntegrityStatus(): array
    {
        $lastCheck = Cache::get('last_integrity_check');
        $lastResult = Cache::get('last_integrity_result');

        return [
            'last_check' => $lastCheck,
            'last_result' => $lastResult,
            'status' => $lastResult && isset($lastResult['verification_passed']) && $lastResult['verification_passed'] ? 'healthy' : 'warning',
            'score' => $lastResult['integrity_score'] ?? 100,
        ];
    }

    /**
     * Calculate efficiency score.
     */
    protected function calculateEfficiencyScore($moderatorId, $dateFrom, $dateTo): float
    {
        $reportsHandled = Report::where('moderator_id', $moderatorId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

        $avgResolutionTime = Report::where('moderator_id', $moderatorId)
            ->whereNotNull('resolved_at')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
            ->value('avg_hours') ?? 0;

        // Base score: 100 points
        $score = 100;

        // Deduct points for slow resolution (target: < 24 hours)
        if ($avgResolutionTime > 24) {
            $score -= min(30, ($avgResolutionTime - 24) * 2);
        }

        // Add points for volume (bonus for > 50 reports)
        if ($reportsHandled > 50) {
            $score += min(20, ($reportsHandled - 50) / 2);
        }

        return max(0, min(100, $score));
    }
}