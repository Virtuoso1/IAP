<?php

namespace App\Services;

use App\Models\Report;
use App\Models\Warning;
use App\Models\UserRestriction;
use App\Models\ModerationAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ModeratorMisconductService
{
    /**
     * Analyze moderator behavior for potential misconduct.
     */
    public function analyzeModeratorBehavior($moderatorId, $dateRange = 30): array
    {
        try {
            $startDate = now()->subDays($dateRange);
            
            $metrics = $this->calculateBehaviorMetrics($moderatorId, $startDate);
            $riskScore = $this->calculateRiskScore($metrics);
            $anomalies = $this->detectAnomalies($moderatorId, $startDate);
            $patterns = $this->detectPatterns($moderatorId, $startDate);
            
            return [
                'moderator_id' => $moderatorId,
                'analysis_period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => now()->toDateString(),
                    'days_analyzed' => $dateRange
                ],
                'metrics' => $metrics,
                'risk_score' => $riskScore,
                'anomalies' => $anomalies,
                'patterns' => $patterns,
                'recommendations' => $this->generateRecommendations($riskScore, $anomalies, $patterns),
                'last_analyzed' => now()->toISOString()
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to analyze moderator behavior', [
                'moderator_id' => $moderatorId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => 'Analysis failed',
                'moderator_id' => $moderatorId,
                'last_analyzed' => now()->toISOString()
            ];
        }
    }

    /**
     * Calculate behavior metrics for a moderator.
     */
    protected function calculateBehaviorMetrics($moderatorId, $startDate): array
    {
        $reportsHandled = Report::where('moderator_id', $moderatorId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $reportsResolved = Report::where('moderator_id', $moderatorId)
            ->where('status', 'resolved')
            ->where('created_at', '>=', $startDate)
            ->count();

        $warningsIssued = Warning::where('moderator_id', $moderatorId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $restrictionsApplied = UserRestriction::where('moderator_id', $moderatorId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $averageResolutionTime = Report::where('moderator_id', $moderatorId)
            ->whereNotNull('resolved_at')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_minutes')
            ->value('avg_minutes') ?? 0;

        $reversedDecisions = $this->countReversedDecisions($moderatorId, $startDate);

        $selfReports = Report::where('reporter_id', $moderatorId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $reportsAgainstModerator = Report::whereHas('reportable', function ($query) use ($moderatorId) {
            $query->where('user_id', $moderatorId);
        })
        ->where('created_at', '>=', $startDate)
        ->count();

        return [
            'reports_handled' => $reportsHandled,
            'reports_resolved' => $reportsResolved,
            'resolution_rate' => $reportsHandled > 0 ? round(($reportsResolved / $reportsHandled) * 100, 2) : 0,
            'warnings_issued' => $warningsIssued,
            'restrictions_applied' => $restrictionsApplied,
            'average_resolution_time_minutes' => round($averageResolutionTime, 2),
            'reversed_decisions' => $reversedDecisions,
            'reversal_rate' => $reportsHandled > 0 ? round(($reversedDecisions / $reportsHandled) * 100, 2) : 0,
            'self_reports' => $selfReports,
            'reports_against_moderator' => $reportsAgainstModerator,
            'severity_distribution' => $this->getSeverityDistribution($moderatorId, $startDate),
            'time_distribution' => $this->getTimeDistribution($moderatorId, $startDate),
            'category_preferences' => $this->getCategoryPreferences($moderatorId, $startDate)
        ];
    }

    /**
     * Calculate risk score based on behavior metrics.
     */
    protected function calculateRiskScore(array $metrics): array
    {
        $score = 0;
        $factors = [];

        // High reversal rate (over 20%)
        if ($metrics['reversal_rate'] > 20) {
            $score += 30;
            $factors[] = 'High reversal rate: ' . $metrics['reversal_rate'] . '%';
        }

        // Very fast resolution times (under 2 minutes average)
        if ($metrics['average_resolution_time_minutes'] < 2 && $metrics['reports_handled'] > 10) {
            $score += 25;
            $factors[] = 'Suspiciously fast resolution times';
        }

        // High restriction rate (over 15% of handled reports)
        if ($metrics['reports_handled'] > 0) {
            $restrictionRate = ($metrics['restrictions_applied'] / $metrics['reports_handled']) * 100;
            if ($restrictionRate > 15) {
                $score += 20;
                $factors[] = 'High restriction rate: ' . round($restrictionRate, 2) . '%';
            }
        }

        // Reports against moderator
        if ($metrics['reports_against_moderator'] > 0) {
            $score += $metrics['reports_against_moderator'] * 10;
            $factors[] = 'Reports filed against moderator: ' . $metrics['reports_against_moderator'];
        }

        // Low resolution rate (under 70%)
        if ($metrics['resolution_rate'] < 70 && $metrics['reports_handled'] > 10) {
            $score += 15;
            $factors[] = 'Low resolution rate: ' . $metrics['resolution_rate'] . '%';
        }

        // Unusual time patterns
        if (isset($metrics['time_distribution']['unusual_hours']) && $metrics['time_distribution']['unusual_hours'] > 30) {
            $score += 15;
            $factors[] = 'High activity during unusual hours';
        }

        // Category bias
        if (isset($metrics['category_preferences']['bias_score']) && $metrics['category_preferences']['bias_score'] > 0.7) {
            $score += 20;
            $factors[] = 'Potential category bias in decisions';
        }

        $riskLevel = 'low';
        if ($score >= 70) {
            $riskLevel = 'critical';
        } elseif ($score >= 50) {
            $riskLevel = 'high';
        } elseif ($score >= 30) {
            $riskLevel = 'medium';
        }

        return [
            'score' => min(100, $score),
            'level' => $riskLevel,
            'factors' => $factors,
            'calculated_at' => now()->toISOString()
        ];
    }

    /**
     * Detect anomalies in moderator behavior.
     */
    protected function detectAnomalies($moderatorId, $startDate): array
    {
        $anomalies = [];

        // Check for unusually fast resolutions
        $fastResolutions = Report::where('moderator_id', $moderatorId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('resolved_at')
            ->whereRaw('TIMESTAMPDIFF(SECOND, created_at, resolved_at) < 30')
            ->count();

        if ($fastResolutions > 5) {
            $anomalies[] = [
                'type' => 'fast_resolutions',
                'severity' => 'high',
                'description' => "{$fastResolutions} reports resolved in under 30 seconds",
                'count' => $fastResolutions
            ];
        }

        // Check for batch processing patterns
        $batchPatterns = $this->detectBatchProcessing($moderatorId, $startDate);
        if ($batchPatterns > 0) {
            $anomalies[] = [
                'type' => 'batch_processing',
                'severity' => 'medium',
                'description' => "{$batchPatterns} instances of batch processing detected",
                'count' => $batchPatterns
            ];
        }

        // Check for repetitive decisions
        $repetitiveDecisions = $this->detectRepetitiveDecisions($moderatorId, $startDate);
        if ($repetitiveDecisions > 0) {
            $anomalies[] = [
                'type' => 'repetitive_decisions',
                'severity' => 'medium',
                'description' => "{$repetitiveDecisions} instances of repetitive decision patterns",
                'count' => $repetitiveDecisions
            ];
        }

        // Check for unusual activity patterns
        $unusualActivity = $this->detectUnusualActivity($moderatorId, $startDate);
        if ($unusualActivity > 0) {
            $anomalies[] = [
                'type' => 'unusual_activity',
                'severity' => 'low',
                'description' => "{$unusualActivity} instances of unusual activity patterns",
                'count' => $unusualActivity
            ];
        }

        return $anomalies;
    }

    /**
     * Detect behavioral patterns.
     */
    protected function detectPatterns($moderatorId, $startDate): array
    {
        $patterns = [];

        // Time-based patterns
        $timePatterns = $this->analyzeTimePatterns($moderatorId, $startDate);
        if (!empty($timePatterns)) {
            $patterns['time_patterns'] = $timePatterns;
        }

        // Decision patterns
        $decisionPatterns = $this->analyzeDecisionPatterns($moderatorId, $startDate);
        if (!empty($decisionPatterns)) {
            $patterns['decision_patterns'] = $decisionPatterns;
        }

        // User interaction patterns
        $interactionPatterns = $this->analyzeInteractionPatterns($moderatorId, $startDate);
        if (!empty($interactionPatterns)) {
            $patterns['interaction_patterns'] = $interactionPatterns;
        }

        // Escalation patterns
        $escalationPatterns = $this->analyzeEscalationPatterns($moderatorId, $startDate);
        if (!empty($escalationPatterns)) {
            $patterns['escalation_patterns'] = $escalationPatterns;
        }

        return $patterns;
    }

    /**
     * Generate recommendations based on analysis.
     */
    protected function generateRecommendations(array $riskScore, array $anomalies, array $patterns): array
    {
        $recommendations = [];

        if ($riskScore['level'] === 'critical') {
            $recommendations[] = [
                'priority' => 'urgent',
                'action' => 'immediate_review',
                'description' => 'Immediate supervisor review required due to critical risk score',
                'auto_escalate' => true
            ];
        }

        if ($riskScore['level'] === 'high') {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'enhanced_monitoring',
                'description' => 'Implement enhanced monitoring and periodic reviews',
                'auto_escalate' => false
            ];
        }

        // Check for specific anomaly-based recommendations
        foreach ($anomalies as $anomaly) {
            switch ($anomaly['type']) {
                case 'fast_resolutions':
                    $recommendations[] = [
                        'priority' => 'high',
                        'action' => 'review_resolution_quality',
                        'description' => 'Review quality of fast resolutions for thoroughness',
                        'auto_escalate' => false
                    ];
                    break;
                case 'batch_processing':
                    $recommendations[] = [
                        'priority' => 'medium',
                        'action' => 'investigate_batch_patterns',
                        'description' => 'Investigate batch processing patterns for potential automation',
                        'auto_escalate' => false
                    ];
                    break;
            }
        }

        // Pattern-based recommendations
        if (isset($patterns['time_patterns']['unusual_hours'])) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'review_work_schedule',
                'description' => 'Review work schedule and activity during unusual hours',
                'auto_escalate' => false
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'priority' => 'low',
                'action' => 'continue_monitoring',
                'description' => 'Continue normal monitoring procedures',
                'auto_escalate' => false
            ];
        }

        return $recommendations;
    }

    /**
     * Count reversed decisions.
     */
    protected function countReversedDecisions($moderatorId, $startDate): int
    {
        return Report::where('moderator_id', $moderatorId)
            ->where('created_at', '>=', $startDate)
            ->whereHas('appeals', function ($query) {
                $query->where('status', 'approved');
            })
            ->count();
    }

    /**
     * Get severity distribution of decisions.
     */
    protected function getSeverityDistribution($moderatorId, $startDate): array
    {
        $warnings = Warning::where('moderator_id', $moderatorId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();

        $restrictions = UserRestriction::where('moderator_id', $moderatorId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return [
            'warnings' => $warnings,
            'restrictions' => $restrictions
        ];
    }

    /**
     * Get time distribution of moderator activity.
     */
    protected function getTimeDistribution($moderatorId, $startDate): array
    {
        $reports = Report::where('moderator_id', $moderatorId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $hourlyDistribution = [];
        $unusualHours = 0;

        foreach ($reports as $report) {
            $hourlyDistribution[$report->hour] = $report->count;
            
            // Count activity during unusual hours (10 PM - 6 AM)
            if ($report->hour >= 22 || $report->hour <= 6) {
                $unusualHours += $report->count;
            }
        }

        return [
            'hourly_distribution' => $hourlyDistribution,
            'unusual_hours' => $unusualHours,
            'unusual_hours_percentage' => $reports->sum('count') > 0 
                ? round(($unusualHours / $reports->sum('count')) * 100, 2) 
                : 0
        ];
    }

    /**
     * Get category preferences.
     */
    protected function getCategoryPreferences($moderatorId, $startDate): array
    {
        $categories = Report::where('moderator_id', $moderatorId)
            ->where('created_at', '>=', $startDate)
            ->with('category')
            ->selectRaw('category_id, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderBy('count', 'desc')
            ->get();

        $total = $categories->sum('count');
        $distribution = [];
        
        foreach ($categories as $category) {
            $distribution[] = [
                'category_id' => $category->category_id,
                'category_name' => $category->category->name ?? 'Unknown',
                'count' => $category->count,
                'percentage' => $total > 0 ? round(($category->count / $total) * 100, 2) : 0
            ];
        }

        // Calculate bias score (if one category dominates > 50%)
        $biasScore = 0;
        if (!empty($distribution) && $distribution[0]['percentage'] > 50) {
            $biasScore = $distribution[0]['percentage'] / 100;
        }

        return [
            'distribution' => $distribution,
            'bias_score' => $biasScore
        ];
    }

    /**
     * Detect batch processing patterns.
     */
    protected function detectBatchProcessing($moderatorId, $startDate): int
    {
        $reports = Report::where('moderator_id', $moderatorId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('resolved_at')
            ->orderBy('resolved_at')
            ->get();

        $batchCount = 0;
        $windowSize = 60; // 1 minute window

        for ($i = 0; $i < $reports->count() - 1; $i++) {
            $current = $reports[$i];
            $next = $reports[$i + 1];
            
            $timeDiff = $current->resolved_at->diffInSeconds($next->resolved_at);
            
            if ($timeDiff <= $windowSize) {
                $batchCount++;
            }
        }

        return $batchCount;
    }

    /**
     * Detect repetitive decisions.
     */
    protected function detectRepetitiveDecisions($moderatorId, $startDate): int
    {
        $reports = Report::where('moderator_id', $moderatorId)
            ->where('created_at', '>=', $startDate)
            ->where('status', 'resolved')
            ->select('resolution', 'created_at')
            ->orderBy('created_at')
            ->get();

        $repetitiveCount = 0;
        $consecutiveSame = 1;
        $lastResolution = null;

        foreach ($reports as $report) {
            if ($lastResolution === $report->resolution) {
                $consecutiveSame++;
                if ($consecutiveSame >= 5) {
                    $repetitiveCount++;
                }
            } else {
                $consecutiveSame = 1;
            }
            $lastResolution = $report->resolution;
        }

        return $repetitiveCount;
    }

    /**
     * Detect unusual activity patterns.
     */
    protected function detectUnusualActivity($moderatorId, $startDate): int
    {
        // This would implement more sophisticated pattern detection
        // For now, return a placeholder
        return 0;
    }

    /**
     * Analyze time patterns.
     */
    protected function analyzeTimePatterns($moderatorId, $startDate): array
    {
        $patterns = [];
        
        $timeDistribution = $this->getTimeDistribution($moderatorId, $startDate);
        
        if ($timeDistribution['unusual_hours_percentage'] > 30) {
            $patterns[] = [
                'type' => 'unusual_hours_activity',
                'description' => 'High activity during unusual hours (10 PM - 6 AM)',
                'percentage' => $timeDistribution['unusual_hours_percentage']
            ];
        }

        return $patterns;
    }

    /**
     * Analyze decision patterns.
     */
    protected function analyzeDecisionPatterns($moderatorId, $startDate): array
    {
        $patterns = [];
        
        $severityDistribution = $this->getSeverityDistribution($moderatorId, $startDate);
        
        // Check for preference towards severe punishments
        $totalWarnings = array_sum($severityDistribution['warnings'] ?? []);
        $severeWarnings = ($severityDistribution['warnings']['high'] ?? 0) + ($severityDistribution['warnings']['critical'] ?? 0);
        
        if ($totalWarnings > 0 && ($severeWarnings / $totalWarnings) > 0.6) {
            $patterns[] = [
                'type' => 'severe_punishment_preference',
                'description' => 'High proportion of severe warnings issued',
                'percentage' => round(($severeWarnings / $totalWarnings) * 100, 2)
            ];
        }

        return $patterns;
    }

    /**
     * Analyze interaction patterns.
     */
    protected function analyzeInteractionPatterns($moderatorId, $startDate): array
    {
        $patterns = [];
        
        // This would implement user interaction analysis
        // For now, return empty array
        
        return $patterns;
    }

    /**
     * Analyze escalation patterns.
     */
    protected function analyzeEscalationPatterns($moderatorId, $startDate): array
    {
        $patterns = [];
        
        $escalations = Warning::where('moderator_id', $moderatorId)
            ->where('created_at', '>=', $startDate)
            ->where('status', 'escalated')
            ->count();

        $totalWarnings = Warning::where('moderator_id', $moderatorId)
            ->where('created_at', '>=', $startDate)
            ->count();

        if ($totalWarnings > 0 && ($escalations / $totalWarnings) > 0.2) {
            $patterns[] = [
                'type' => 'high_escalation_rate',
                'description' => 'High rate of escalated warnings',
                'percentage' => round(($escalations / $totalWarnings) * 100, 2)
            ];
        }

        return $patterns;
    }

    /**
     * Get all moderators for analysis.
     */
    public function getAllModerators(): array
    {
        return User::whereHas('reports', function ($query) {
            $query->whereNotNull('moderator_id');
        })
        ->orWhereHas('warnings')
        ->orWhereHas('userRestrictions')
        ->distinct()
        ->pluck('id')
        ->toArray();
    }

    /**
     * Run analysis on all moderators.
     */
    public function analyzeAllModerators($dateRange = 30): array
    {
        $moderators = $this->getAllModerators();
        $results = [];

        foreach ($moderators as $moderatorId) {
            $results[$moderatorId] = $this->analyzeModeratorBehavior($moderatorId, $dateRange);
        }

        return $results;
    }

    /**
     * Get high-risk moderators.
     */
    public function getHighRiskModerators($dateRange = 30): array
    {
        $allResults = $this->analyzeAllModerators($dateRange);
        $highRisk = [];

        foreach ($allResults as $moderatorId => $result) {
            if (isset($result['risk_score']['level']) && 
                in_array($result['risk_score']['level'], ['high', 'critical'])) {
                $highRisk[$moderatorId] = $result;
            }
        }

        return $highRisk;
    }
}