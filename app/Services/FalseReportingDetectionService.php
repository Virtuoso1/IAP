<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use App\Models\Warning;
use App\Models\UserRestriction;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class FalseReportingDetectionService
{
    /**
     * Analyze user reporting patterns for false reporting detection.
     */
    public function analyzeUserReportingPatterns($userId, $dateRange = 90): array
    {
        try {
            $startDate = now()->subDays($dateRange);
            
            $metrics = $this->calculateReportingMetrics($userId, $startDate);
            $riskScore = $this->calculateFalseReportingRisk($metrics);
            $patterns = $this->detectFalseReportingPatterns($userId, $startDate);
            $recommendations = $this->generateFalseReportingRecommendations($riskScore, $patterns);
            
            return [
                'user_id' => $userId,
                'analysis_period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => now()->toDateString(),
                    'days_analyzed' => $dateRange
                ],
                'metrics' => $metrics,
                'risk_score' => $riskScore,
                'patterns' => $patterns,
                'recommendations' => $recommendations,
                'last_analyzed' => now()->toISOString()
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to analyze user reporting patterns', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => 'Analysis failed',
                'user_id' => $userId,
                'last_analyzed' => now()->toISOString()
            ];
        }
    }

    /**
     * Calculate reporting metrics for a user.
     */
    protected function calculateReportingMetrics($userId, $startDate): array
    {
        $totalReports = Report::where('reporter_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $dismissedReports = Report::where('reporter_id', $userId)
            ->where('status', 'dismissed')
            ->where('created_at', '>=', $startDate)
            ->count();

        $resolvedReports = Report::where('reporter_id', $userId)
            ->where('status', 'resolved')
            ->where('created_at', '>=', $startDate)
            ->count();

        $reportsAgainstSameUser = $this->getReportsAgainstSameUser($userId, $startDate);
        $reportsInShortTimeframe = $this->getReportsInShortTimeframe($userId, $startDate);
        $retractedReports = $this->getRetractedReports($userId, $startDate);
        $duplicateReports = $this->getDuplicateReports($userId, $startDate);

        $dismissalRate = $totalReports > 0 ? round(($dismissedReports / $totalReports) * 100, 2) : 0;
        $successRate = $totalReports > 0 ? round(($resolvedReports / $totalReports) * 100, 2) : 0;

        return [
            'total_reports' => $totalReports,
            'dismissed_reports' => $dismissedReports,
            'resolved_reports' => $resolvedReports,
            'dismissal_rate' => $dismissalRate,
            'success_rate' => $successRate,
            'reports_against_same_user' => $reportsAgainstSameUser,
            'reports_in_short_timeframe' => $reportsInShortTimeframe,
            'retracted_reports' => $retractedReports,
            'duplicate_reports' => $duplicateReports,
            'category_distribution' => $this->getCategoryDistribution($userId, $startDate),
            'time_distribution' => $this->getReportingTimeDistribution($userId, $startDate),
            'target_distribution' => $this->getTargetDistribution($userId, $startDate)
        ];
    }

    /**
     * Calculate false reporting risk score.
     */
    protected function calculateFalseReportingRisk(array $metrics): array
    {
        $score = 0;
        $factors = [];

        // High dismissal rate (over 70%)
        if ($metrics['dismissal_rate'] > 70) {
            $score += 40;
            $factors[] = 'Very high dismissal rate: ' . $metrics['dismissal_rate'] . '%';
        } elseif ($metrics['dismissal_rate'] > 50) {
            $score += 25;
            $factors[] = 'High dismissal rate: ' . $metrics['dismissal_rate'] . '%';
        }

        // Low success rate (under 20%)
        if ($metrics['success_rate'] < 20 && $metrics['total_reports'] > 5) {
            $score += 30;
            $factors[] = 'Very low success rate: ' . $metrics['success_rate'] . '%';
        } elseif ($metrics['success_rate'] < 30 && $metrics['total_reports'] > 5) {
            $score += 15;
            $factors[] = 'Low success rate: ' . $metrics['success_rate'] . '%';
        }

        // Reports against same user
        if ($metrics['reports_against_same_user'] > 5) {
            $score += 20;
            $factors[] = 'Excessive reports against same user: ' . $metrics['reports_against_same_user'];
        }

        // Reports in short timeframe
        if ($metrics['reports_in_short_timeframe'] > 3) {
            $score += 25;
            $factors[] = 'Multiple reports in short timeframe: ' . $metrics['reports_in_short_timeframe'];
        }

        // Retracted reports
        if ($metrics['retracted_reports'] > 2) {
            $score += 15;
            $factors[] = 'Multiple retracted reports: ' . $metrics['retracted_reports'];
        }

        // Duplicate reports
        if ($metrics['duplicate_reports'] > 3) {
            $score += 20;
            $factors[] = 'Multiple duplicate reports: ' . $metrics['duplicate_reports'];
        }

        // Target concentration
        if (isset($metrics['target_distribution']['concentration_score']) && 
            $metrics['target_distribution']['concentration_score'] > 0.6) {
            $score += 15;
            $factors[] = 'High concentration of reports against specific users';
        }

        $riskLevel = 'low';
        if ($score >= 80) {
            $riskLevel = 'critical';
        } elseif ($score >= 60) {
            $riskLevel = 'high';
        } elseif ($score >= 40) {
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
     * Detect false reporting patterns.
     */
    protected function detectFalseReportingPatterns($userId, $startDate): array
    {
        $patterns = [];

        // Pattern: Targeted harassment
        $harassmentPattern = $this->detectTargetedHarassment($userId, $startDate);
        if ($harassmentPattern) {
            $patterns[] = $harassmentPattern;
        }

        // Pattern: Batch reporting
        $batchPattern = $this->detectBatchReporting($userId, $startDate);
        if ($batchPattern) {
            $patterns[] = $batchPattern;
        }

        // Pattern: Retraction pattern
        $retractionPattern = $this->detectRetractionPattern($userId, $startDate);
        if ($retractionPattern) {
            $patterns[] = $retractionPattern;
        }

        // Pattern: Category manipulation
        $categoryPattern = $this->detectCategoryManipulation($userId, $startDate);
        if ($categoryPattern) {
            $patterns[] = $categoryPattern;
        }

        // Pattern: Timing manipulation
        $timingPattern = $this->detectTimingManipulation($userId, $startDate);
        if ($timingPattern) {
            $patterns[] = $timingPattern;
        }

        return $patterns;
    }

    /**
     * Generate recommendations for false reporting.
     */
    protected function generateFalseReportingRecommendations(array $riskScore, array $patterns): array
    {
        $recommendations = [];

        if ($riskScore['level'] === 'critical') {
            $recommendations[] = [
                'priority' => 'urgent',
                'action' => 'suspend_reporting_privileges',
                'description' => 'Suspend user\'s reporting privileges immediately due to critical false reporting risk',
                'duration_days' => 30,
                'auto_apply' => true
            ];
        }

        if ($riskScore['level'] === 'high') {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'warning_and_monitor',
                'description' => 'Issue formal warning and implement enhanced monitoring',
                'auto_apply' => false
            ];
        }

        if ($riskScore['level'] === 'medium') {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'educational_warning',
                'description' => 'Send educational warning about proper reporting practices',
                'auto_apply' => false
            ];
        }

        // Pattern-specific recommendations
        foreach ($patterns as $pattern) {
            switch ($pattern['type']) {
                case 'targeted_harassment':
                    $recommendations[] = [
                        'priority' => 'high',
                        'action' => 'harassment_investigation',
                        'description' => 'Investigate potential harassment behavior',
                        'auto_apply' => false
                    ];
                    break;
                case 'batch_reporting':
                    $recommendations[] = [
                        'priority' => 'medium',
                        'action' => 'rate_limit_increase',
                        'description' => 'Implement stricter rate limiting for this user',
                        'auto_apply' => true
                    ];
                    break;
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'priority' => 'low',
                'action' => 'continue_monitoring',
                'description' => 'Continue normal monitoring of reporting patterns',
                'auto_apply' => false
            ];
        }

        return $recommendations;
    }

    /**
     * Get reports against same user.
     */
    protected function getReportsAgainstSameUser($userId, $startDate): int
    {
        $reports = Report::where('reporter_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->with('reportable')
            ->get();

        $targetCounts = [];
        
        foreach ($reports as $report) {
            if ($report->reportable && isset($report->reportable->user_id)) {
                $targetId = $report->reportable->user_id;
                $targetCounts[$targetId] = ($targetCounts[$targetId] ?? 0) + 1;
            }
        }

        return max($targetCounts) ?? 0;
    }

    /**
     * Get reports in short timeframe.
     */
    protected function getReportsInShortTimeframe($userId, $startDate): int
    {
        $reports = Report::where('reporter_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at')
            ->get();

        $shortTimeframeCount = 0;
        $windowSize = 300; // 5 minutes

        for ($i = 0; $i < $reports->count() - 1; $i++) {
            $current = $reports[$i];
            $next = $reports[$i + 1];
            
            $timeDiff = $current->created_at->diffInSeconds($next->created_at);
            
            if ($timeDiff <= $windowSize) {
                $shortTimeframeCount++;
            }
        }

        return $shortTimeframeCount;
    }

    /**
     * Get retracted reports.
     */
    protected function getRetractedReports($userId, $startDate): int
    {
        return Report::where('reporter_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->where('status', 'retracted')
            ->count();
    }

    /**
     * Get duplicate reports.
     */
    protected function getDuplicateReports($userId, $startDate): int
    {
        $reports = Report::where('reporter_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $duplicateCount = 0;
        $seenReports = [];

        foreach ($reports as $report) {
            $key = $report->reportable_type . '_' . $report->reportable_id . '_' . $report->category_id;
            
            if (isset($seenReports[$key])) {
                $duplicateCount++;
            } else {
                $seenReports[$key] = true;
            }
        }

        return $duplicateCount;
    }

    /**
     * Get category distribution.
     */
    protected function getCategoryDistribution($userId, $startDate): array
    {
        $categories = Report::where('reporter_id', $userId)
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

        return $distribution;
    }

    /**
     * Get reporting time distribution.
     */
    protected function getReportingTimeDistribution($userId, $startDate): array
    {
        $reports = Report::where('reporter_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $hourlyDistribution = [];
        
        foreach ($reports as $report) {
            $hourlyDistribution[$report->hour] = $report->count;
        }

        return $hourlyDistribution;
    }

    /**
     * Get target distribution.
     */
    protected function getTargetDistribution($userId, $startDate): array
    {
        $reports = Report::where('reporter_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->with('reportable')
            ->get();

        $targetCounts = [];
        $totalReports = $reports->count();
        
        foreach ($reports as $report) {
            if ($report->reportable && isset($report->reportable->user_id)) {
                $targetId = $report->reportable->user_id;
                $targetCounts[$targetId] = ($targetCounts[$targetId] ?? 0) + 1;
            }
        }

        if (empty($targetCounts)) {
            return ['concentration_score' => 0, 'targets' => []];
        }

        $maxCount = max($targetCounts);
        $concentrationScore = $maxCount / $totalReports;

        return [
            'concentration_score' => round($concentrationScore, 2),
            'unique_targets' => count($targetCounts),
            'most_targeted_count' => $maxCount,
            'targets' => $targetCounts
        ];
    }

    /**
     * Detect targeted harassment pattern.
     */
    protected function detectTargetedHarassment($userId, $startDate): ?array
    {
        $targetDistribution = $this->getTargetDistribution($userId, $startDate);
        
        if ($targetDistribution['concentration_score'] > 0.5 && $targetDistribution['most_targeted_count'] >= 5) {
            return [
                'type' => 'targeted_harassment',
                'severity' => 'high',
                'description' => 'User appears to be targeting specific users with repeated reports',
                'concentration_score' => $targetDistribution['concentration_score'],
                'most_targeted_count' => $targetDistribution['most_targeted_count']
            ];
        }

        return null;
    }

    /**
     * Detect batch reporting pattern.
     */
    protected function detectBatchReporting($userId, $startDate): ?array
    {
        $shortTimeframeReports = $this->getReportsInShortTimeframe($userId, $startDate);
        
        if ($shortTimeframeReports >= 3) {
            return [
                'type' => 'batch_reporting',
                'severity' => 'medium',
                'description' => 'User submits multiple reports in quick succession',
                'batch_count' => $shortTimeframeReports
            ];
        }

        return null;
    }

    /**
     * Detect retraction pattern.
     */
    protected function detectRetractionPattern($userId, $startDate): ?array
    {
        $retractedReports = $this->getRetractedReports($userId, $startDate);
        $totalReports = Report::where('reporter_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->count();

        if ($totalReports > 0 && ($retractedReports / $totalReports) > 0.3) {
            return [
                'type' => 'retraction_pattern',
                'severity' => 'medium',
                'description' => 'High rate of report retractions suggests false reporting',
                'retraction_rate' => round(($retractedReports / $totalReports) * 100, 2)
            ];
        }

        return null;
    }

    /**
     * Detect category manipulation.
     */
    protected function detectCategoryManipulation($userId, $startDate): ?array
    {
        $categoryDistribution = $this->getCategoryDistribution($userId, $startDate);
        
        if (!empty($categoryDistribution) && $categoryDistribution[0]['percentage'] > 70) {
            return [
                'type' => 'category_manipulation',
                'severity' => 'low',
                'description' => 'User predominantly uses one category, potentially for manipulation',
                'dominant_category' => $categoryDistribution[0]['category_name'],
                'percentage' => $categoryDistribution[0]['percentage']
            ];
        }

        return null;
    }

    /**
     * Detect timing manipulation.
     */
    protected function detectTimingManipulation($userId, $startDate): ?array
    {
        $timeDistribution = $this->getReportingTimeDistribution($userId, $startDate);
        
        // Check for unusual timing patterns (e.g., always reporting at specific times)
        if (count($timeDistribution) <= 3) {
            return [
                'type' => 'timing_manipulation',
                'severity' => 'low',
                'description' => 'User reports at very specific times, suggesting automated or coordinated behavior',
                'active_hours' => count($timeDistribution)
            ];
        }

        return null;
    }

    /**
     * Apply automatic penalties for false reporting.
     */
    public function applyAutomaticPenalties($userId, array $analysis): array
    {
        $penalties = [];
        
        if ($analysis['risk_score']['level'] === 'critical') {
            // Suspend reporting privileges
            $restriction = $this->suspendReportingPrivileges($userId, 30);
            if ($restriction) {
                $penalties[] = [
                    'type' => 'reporting_suspension',
                    'duration_days' => 30,
                    'reason' => 'Critical false reporting risk detected',
                    'restriction_id' => $restriction->id
                ];
            }
        }

        if ($analysis['risk_score']['level'] === 'high') {
            // Issue warning
            $warning = $this->issueFalseReportingWarning($userId);
            if ($warning) {
                $penalties[] = [
                    'type' => 'warning',
                    'level' => 'medium',
                    'reason' => 'High false reporting risk detected',
                    'warning_id' => $warning->id
                ];
            }
        }

        // Apply rate limiting for medium risk
        if ($analysis['risk_score']['level'] === 'medium') {
            $this->applyRateLimiting($userId);
            $penalties[] = [
                'type' => 'rate_limiting',
                'description' => 'Stricter rate limiting applied'
            ];
        }

        return $penalties;
    }

    /**
     * Suspend user's reporting privileges.
     */
    protected function suspendReportingPrivileges($userId, $durationDays): ?UserRestriction
    {
        try {
            return UserRestriction::create([
                'user_id' => $userId,
                'moderator_id' => 1, // System moderator
                'type' => 'no_reporting',
                'reason' => 'Automatic suspension due to false reporting patterns',
                'description' => 'User\'s reporting privileges suspended due to detected false reporting patterns',
                'is_permanent' => false,
                'expires_at' => now()->addDays($durationDays),
                'metadata' => [
                    'automatic' => true,
                    'false_reporting_detection' => true
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to suspend reporting privileges', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Issue warning for false reporting.
     */
    protected function issueFalseReportingWarning($userId): ?Warning
    {
        try {
            return Warning::create([
                'user_id' => $userId,
                'moderator_id' => 1, // System moderator
                'level' => 'medium',
                'type' => 'false_reporting',
                'reason' => 'False reporting patterns detected',
                'description' => 'User has exhibited patterns consistent with false reporting',
                'expires_at' => now()->addDays(30),
                'metadata' => [
                    'automatic' => true,
                    'false_reporting_detection' => true
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to issue false reporting warning', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Apply rate limiting to user.
     */
    protected function applyRateLimiting($userId): void
    {
        // Store rate limiting information in cache
        Cache::put(
            "user_rate_limit_{$userId}",
            [
                'reports_per_hour' => 2,
                'reports_per_day' => 5,
                'applied_at' => now(),
                'duration' => 30 // days
            ],
            now()->addDays(30)
        );
    }

    /**
     * Get all users for false reporting analysis.
     */
    public function getAllReporters(): array
    {
        return User::whereHas('reports')
            ->distinct()
            ->pluck('id')
            ->toArray();
    }

    /**
     * Run analysis on all reporters.
     */
    public function analyzeAllReporters($dateRange = 90): array
    {
        $reporters = $this->getAllReporters();
        $results = [];

        foreach ($reporters as $userId) {
            $results[$userId] = $this->analyzeUserReportingPatterns($userId, $dateRange);
        }

        return $results;
    }

    /**
     * Get high-risk false reporters.
     */
    public function getHighRiskFalseReporters($dateRange = 90): array
    {
        $allResults = $this->analyzeAllReporters($dateRange);
        $highRisk = [];

        foreach ($allResults as $userId => $result) {
            if (isset($result['risk_score']['level']) && 
                in_array($result['risk_score']['level'], ['high', 'critical'])) {
                $highRisk[$userId] = $result;
            }
        }

        return $highRisk;
    }
}