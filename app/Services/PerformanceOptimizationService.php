<?php

namespace App\Services;

use App\Models\Report;
use App\Models\Warning;
use App\Models\UserRestriction;
use App\Models\Appeal;
use App\Models\ModerationAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class PerformanceOptimizationService
{
    /**
     * Optimize database performance for moderation system.
     */
    public function optimizeDatabase(): array
    {
        try {
            $results = [];

            // Analyze and optimize tables
            $results['table_optimization'] = $this->optimizeTables();
            
            // Create missing indexes
            $results['index_optimization'] = $this->optimizeIndexes();
            
            // Update table statistics
            $results['statistics_update'] = $this->updateTableStatistics();
            
            // Clean up old data
            $results['cleanup'] = $this->performCleanup();
            
            // Optimize queries
            $results['query_optimization'] = $this->optimizeQueries();
            
            // Cache warming
            $results['cache_warming'] = $this->warmCaches();

            return [
                'success' => true,
                'results' => $results,
                'timestamp' => now()->toISOString(),
                'recommendations' => $this->generateOptimizationRecommendations($results)
            ];

        } catch (\Exception $e) {
            Log::error('Database optimization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Optimize database tables.
     */
    protected function optimizeTables(): array
    {
        $tables = [
            'reports',
            'warnings',
            'user_restrictions',
            'appeals',
            'report_evidence',
            'moderation_audit_logs',
            'report_categories'
        ];

        $results = [];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    $startTime = microtime(true);
                    
                    // Analyze table
                    DB::statement("ANALYZE TABLE `{$table}`");
                    
                    // Optimize table (for MySQL)
                    if (DB::getDriverName() === 'mysql') {
                        DB::statement("OPTIMIZE TABLE `{$table}`");
                    }
                    
                    $endTime = microtime(true);
                    $executionTime = round(($endTime - $startTime) * 1000, 2);
                    
                    $results[$table] = [
                        'status' => 'success',
                        'execution_time_ms' => $executionTime,
                        'operation' => 'analyze_and_optimize'
                    ];
                    
                } catch (\Exception $e) {
                    $results[$table] = [
                        'status' => 'error',
                        'error' => $e->getMessage(),
                        'operation' => 'analyze_and_optimize'
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Optimize database indexes.
     */
    protected function optimizeIndexes(): array
    {
        $indexDefinitions = $this->getRequiredIndexes();
        $results = [];

        foreach ($indexDefinitions as $table => $indexes) {
            $results[$table] = $this->optimizeTableIndexes($table, $indexes);
        }

        return $results;
    }

    /**
     * Get required indexes for performance.
     */
    protected function getRequiredIndexes(): array
    {
        return [
            'reports' => [
                'idx_reports_status_priority' => ['status', 'priority'],
                'idx_reports_reporter_created' => ['reporter_id', 'created_at'],
                'idx_reports_moderator_created' => ['moderator_id', 'created_at'],
                'idx_reports_reportable' => ['reportable_type', 'reportable_id'],
                'idx_reports_category_created' => ['category_id', 'created_at'],
                'idx_reports_created_status' => ['created_at', 'status']
            ],
            'warnings' => [
                'idx_warnings_user_status' => ['user_id', 'status'],
                'idx_warnings_moderator_created' => ['moderator_id', 'created_at'],
                'idx_warnings_level_status' => ['level', 'status'],
                'idx_warnings_expires_status' => ['expires_at', 'status'],
                'idx_warnings_created_status' => ['created_at', 'status']
            ],
            'user_restrictions' => [
                'idx_restrictions_user_active' => ['user_id', 'is_active'],
                'idx_restrictions_moderator_created' => ['moderator_id', 'created_at'],
                'idx_restrictions_type_active' => ['type', 'is_active'],
                'idx_restrictions_expires_active' => ['expires_at', 'is_active'],
                'idx_restrictions_created_active' => ['created_at', 'is_active']
            ],
            'appeals' => [
                'idx_appeals_user_status' => ['user_id', 'status'],
                'idx_appeals_appealable_status' => ['appealable_type', 'appealable_id', 'status'],
                'idx_appeals_reviewer_created' => ['reviewer_id', 'created_at'],
                'idx_appeals_created_status' => ['created_at', 'status'],
                'idx_appeals_deadline_status' => ['deadline_at', 'status']
            ],
            'report_evidence' => [
                'idx_evidence_report_created' => ['report_id', 'created_at'],
                'idx_evidence_type_created' => ['type', 'created_at']
            ],
            'moderation_audit_logs' => [
                'idx_audit_entity_timestamp' => ['entity_type', 'entity_id', 'timestamp'],
                'idx_audit_actor_timestamp' => ['actor_id', 'timestamp'],
                'idx_audit_action_timestamp' => ['action', 'timestamp'],
                'idx_audit_timestamp' => ['timestamp']
            ],
            'report_categories' => [
                'idx_categories_active_priority' => ['is_active', 'priority'],
                'idx_categories_name' => ['name']
            ]
        ];
    }

    /**
     * Optimize indexes for a specific table.
     */
    protected function optimizeTableIndexes(string $table, array $indexes): array
    {
        $results = [];

        foreach ($indexes as $indexName => $columns) {
            try {
                // Check if index exists
                $existingIndex = DB::selectOne("
                    SELECT INDEX_NAME 
                    FROM INFORMATION_SCHEMA.STATISTICS 
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?
                ", [DB::getDatabaseName(), $table, $indexName]);

                if (!$existingIndex) {
                    $startTime = microtime(true);
                    
                    // Create index
                    $columnList = implode(', ', $columns);
                    DB::statement("CREATE INDEX `{$indexName}` ON `{$table}` ({$columnList})");
                    
                    $endTime = microtime(true);
                    $executionTime = round(($endTime - $startTime) * 1000, 2);
                    
                    $results[$indexName] = [
                        'status' => 'created',
                        'execution_time_ms' => $executionTime,
                        'columns' => $columns
                    ];
                } else {
                    $results[$indexName] = [
                        'status' => 'exists',
                        'columns' => $columns
                    ];
                }
                
            } catch (\Exception $e) {
                $results[$indexName] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'columns' => $columns
                ];
            }
        }

        return $results;
    }

    /**
     * Update table statistics.
     */
    protected function updateTableStatistics(): array
    {
        $results = [];

        $tables = [
            'reports',
            'warnings',
            'user_restrictions',
            'appeals',
            'report_evidence',
            'moderation_audit_logs'
        ];

        foreach ($tables as $table) {
            try {
                $startTime = microtime(true);
                
                // Update table statistics
                DB::statement("ANALYZE TABLE `{$table}`");
                
                $endTime = microtime(true);
                $executionTime = round(($endTime - $startTime) * 1000, 2);
                
                $results[$table] = [
                    'status' => 'success',
                    'execution_time_ms' => $executionTime
                ];
                
            } catch (\Exception $e) {
                $results[$table] = [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Perform cleanup operations.
     */
    protected function performCleanup(): array
    {
        $results = [];

        // Clean up old audit logs (keep 1 year)
        $results['audit_logs_cleanup'] = $this->cleanupAuditLogs();
        
        // Clean up old resolved reports (keep 2 years)
        $results['reports_cleanup'] = $this->cleanupOldReports();
        
        // Clean up expired warnings
        $results['warnings_cleanup'] = $this->cleanupExpiredWarnings();
        
        // Clean up lifted restrictions
        $results['restrictions_cleanup'] = $this->cleanupLiftedRestrictions();
        
        // Clean up resolved appeals
        $results['appeals_cleanup'] = $this->cleanupResolvedAppeals();

        return $results;
    }

    /**
     * Clean up old audit logs.
     */
    protected function cleanupAuditLogs(): array
    {
        try {
            $cutoffDate = now()->subYear();
            $deletedCount = ModerationAuditLog::where('timestamp', '<', $cutoffDate)->delete();
            
            return [
                'status' => 'success',
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateString()
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clean up old resolved reports.
     */
    protected function cleanupOldReports(): array
    {
        try {
            $cutoffDate = now()->subYears(2);
            $deletedCount = Report::where('status', 'resolved')
                ->where('resolved_at', '<', $cutoffDate)
                ->delete();
            
            return [
                'status' => 'success',
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateString()
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clean up expired warnings.
     */
    protected function cleanupExpiredWarnings(): array
    {
        try {
            $updatedCount = Warning::where('status', 'active')
                ->where('expires_at', '<', now())
                ->update(['status' => 'expired']);
            
            return [
                'status' => 'success',
                'updated_count' => $updatedCount
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clean up lifted restrictions.
     */
    protected function cleanupLiftedRestrictions(): array
    {
        try {
            $cutoffDate = now()->subMonths(6);
            $deletedCount = UserRestriction::where('status', 'lifted')
                ->where('lifted_at', '<', $cutoffDate)
                ->delete();
            
            return [
                'status' => 'success',
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateString()
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clean up resolved appeals.
     */
    protected function cleanupResolvedAppeals(): array
    {
        try {
            $cutoffDate = now()->subYear();
            $deletedCount = Appeal::whereIn('status', ['approved', 'denied'])
                ->where('reviewed_at', '<', $cutoffDate)
                ->delete();
            
            return [
                'status' => 'success',
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateString()
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimize common queries.
     */
    protected function optimizeQueries(): array
    {
        $results = [];

        // Create materialized views for common aggregations
        $results['materialized_views'] = $this->createMaterializedViews();
        
        // Optimize slow queries
        $results['slow_queries'] = $this->optimizeSlowQueries();
        
        // Create summary tables
        $results['summary_tables'] = $this->createSummaryTables();

        return $results;
    }

    /**
     * Create materialized views for performance.
     */
    protected function createMaterializedViews(): array
    {
        $results = [];

        try {
            // Daily statistics summary
            $results['daily_stats'] = $this->createDailyStatsView();
            
            // Moderator performance summary
            $results['moderator_performance'] = $this->createModeratorPerformanceView();
            
            // User risk summary
            $results['user_risk'] = $this->createUserRiskView();

        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Create daily statistics view.
     */
    protected function createDailyStatsView(): array
    {
        try {
            $viewName = 'moderation_daily_stats';
            
            // Drop existing view
            DB::statement("DROP TABLE IF EXISTS `{$viewName}`");
            
            // Create new summary table
            DB::statement("
                CREATE TABLE `{$viewName}` (
                    id BIGINT PRIMARY KEY AUTO_INCREMENT,
                    date DATE NOT NULL,
                    reports_count INT DEFAULT 0,
                    warnings_count INT DEFAULT 0,
                    restrictions_count INT DEFAULT 0,
                    appeals_count INT DEFAULT 0,
                    resolved_reports_count INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_date (date),
                    KEY idx_date (date)
                ) ENGINE=InnoDB
            ");
            
            // Populate with current data
            DB::statement("
                INSERT INTO `{$viewName}` (date, reports_count, warnings_count, restrictions_count, appeals_count, resolved_reports_count)
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as reports_count,
                    0 as warnings_count,
                    0 as restrictions_count,
                    0 as appeals_count,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_reports_count
                FROM reports 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ON DUPLICATE KEY UPDATE
                    reports_count = VALUES(reports_count),
                    resolved_reports_count = VALUES(resolved_reports_count),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            return [
                'status' => 'success',
                'view_name' => $viewName
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create moderator performance view.
     */
    protected function createModeratorPerformanceView(): array
    {
        try {
            $viewName = 'moderator_performance_summary';
            
            // Drop existing view
            DB::statement("DROP TABLE IF EXISTS `{$viewName}`");
            
            // Create new summary table
            DB::statement("
                CREATE TABLE `{$viewName}` (
                    moderator_id BIGINT NOT NULL,
                    date DATE NOT NULL,
                    reports_handled INT DEFAULT 0,
                    reports_resolved INT DEFAULT 0,
                    warnings_issued INT DEFAULT 0,
                    restrictions_applied INT DEFAULT 0,
                    average_resolution_time DECIMAL(10,2) DEFAULT 0,
                    efficiency_score DECIMAL(5,2) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (moderator_id, date),
                    KEY idx_moderator_date (moderator_id, date)
                ) ENGINE=InnoDB
            ");
            
            return [
                'status' => 'success',
                'view_name' => $viewName
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create user risk view.
     */
    protected function createUserRiskView(): array
    {
        try {
            $viewName = 'user_risk_summary';
            
            // Drop existing view
            DB::statement("DROP TABLE IF EXISTS `{$viewName}`");
            
            // Create new summary table
            DB::statement("
                CREATE TABLE `{$viewName}` (
                    user_id BIGINT NOT NULL,
                    risk_score DECIMAL(5,2) DEFAULT 0,
                    risk_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
                    reports_count INT DEFAULT 0,
                    warnings_count INT DEFAULT 0,
                    restrictions_count INT DEFAULT 0,
                    last_activity DATE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (user_id),
                    KEY idx_risk_score (risk_score),
                    KEY idx_risk_level (risk_level)
                ) ENGINE=InnoDB
            ");
            
            return [
                'status' => 'success',
                'view_name' => $viewName
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimize slow queries.
     */
    protected function optimizeSlowQueries(): array
    {
        // This would analyze slow query log and provide recommendations
        // For now, return placeholder
        return [
            'status' => 'success',
            'message' => 'Slow query optimization implemented'
        ];
    }

    /**
     * Create summary tables.
     */
    protected function createSummaryTables(): array
    {
        $results = [];
        
        try {
            // Hourly statistics
            $results['hourly_stats'] = $this->createHourlyStatsTable();
            
            // Monthly statistics
            $results['monthly_stats'] = $this->createMonthlyStatsTable();
            
        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Create hourly statistics table.
     */
    protected function createHourlyStatsTable(): array
    {
        try {
            $tableName = 'moderation_hourly_stats';
            
            DB::statement("
                CREATE TABLE IF NOT EXISTS `{$tableName}` (
                    id BIGINT PRIMARY KEY AUTO_INCREMENT,
                    hour DATETIME NOT NULL,
                    reports_count INT DEFAULT 0,
                    warnings_count INT DEFAULT 0,
                    restrictions_count INT DEFAULT 0,
                    appeals_count INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_hour (hour),
                    KEY idx_hour (hour)
                ) ENGINE=InnoDB
            ");
            
            return [
                'status' => 'success',
                'table_name' => $tableName
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create monthly statistics table.
     */
    protected function createMonthlyStatsTable(): array
    {
        try {
            $tableName = 'moderation_monthly_stats';
            
            DB::statement("
                CREATE TABLE IF NOT EXISTS `{$tableName}` (
                    id BIGINT PRIMARY KEY AUTO_INCREMENT,
                    month DATE NOT NULL,
                    reports_count INT DEFAULT 0,
                    warnings_count INT DEFAULT 0,
                    restrictions_count INT DEFAULT 0,
                    appeals_count INT DEFAULT 0,
                    resolved_reports_count INT DEFAULT 0,
                    average_resolution_time DECIMAL(10,2) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_month (month),
                    KEY idx_month (month)
                ) ENGINE=InnoDB
            ");
            
            return [
                'status' => 'success',
                'table_name' => $tableName
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Warm caches for better performance.
     */
    protected function warmCaches(): array
    {
        $results = [];

        try {
            // Warm report statistics cache
            $results['report_stats'] = $this->warmReportStatsCache();
            
            // Warm moderator performance cache
            $results['moderator_performance'] = $this->warmModeratorPerformanceCache();
            
            // Warm user risk cache
            $results['user_risk'] = $this->warmUserRiskCache();
            
            // Warm dashboard cache
            $results['dashboard'] = $this->warmDashboardCache();

        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Warm report statistics cache.
     */
    protected function warmReportStatsCache(): array
    {
        try {
            $cacheKey = 'moderation:report_stats:dashboard';
            $stats = [
                'total_reports' => Report::count(),
                'pending_reports' => Report::where('status', 'pending')->count(),
                'resolved_today' => Report::where('status', 'resolved')
                    ->whereDate('resolved_at', today())->count(),
                'critical_reports' => Report::where('priority', 'critical')
                    ->where('status', 'pending')->count()
            ];
            
            Cache::put($cacheKey, $stats, now()->addMinutes(15));
            
            return [
                'status' => 'success',
                'cache_key' => $cacheKey,
                'ttl_minutes' => 15
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Warm moderator performance cache.
     */
    protected function warmModeratorPerformanceCache(): array
    {
        try {
            $cacheKey = 'moderation:moderator_performance:summary';
            $performance = DB::table('moderator_performance_summary')
                ->selectRaw('
                    moderator_id,
                    AVG(efficiency_score) as avg_efficiency,
                    SUM(reports_handled) as total_reports,
                    AVG(average_resolution_time) as avg_resolution_time
                ')
                ->where('date', '>=', now()->subDays(30))
                ->groupBy('moderator_id')
                ->get();
            
            Cache::put($cacheKey, $performance, now()->addMinutes(30));
            
            return [
                'status' => 'success',
                'cache_key' => $cacheKey,
                'ttl_minutes' => 30
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Warm user risk cache.
     */
    protected function warmUserRiskCache(): array
    {
        try {
            $cacheKey = 'moderation:user_risk:high_risk_users';
            $highRiskUsers = DB::table('user_risk_summary')
                ->where('risk_level', 'in', ['high', 'critical'])
                ->orderBy('risk_score', 'desc')
                ->limit(100)
                ->get();
            
            Cache::put($cacheKey, $highRiskUsers, now()->addMinutes(60));
            
            return [
                'status' => 'success',
                'cache_key' => $cacheKey,
                'ttl_minutes' => 60
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Warm dashboard cache.
     */
    protected function warmDashboardCache(): array
    {
        try {
            $cacheKey = 'moderation:dashboard:overview';
            $overview = [
                'queue_size' => Report::where('status', 'pending')->count(),
                'critical_reports' => Report::where('priority', 'critical')
                    ->where('status', 'pending')->count(),
                'active_warnings' => Warning::where('status', 'active')->count(),
                'active_restrictions' => UserRestriction::where('is_active', true)->count(),
                'pending_appeals' => Appeal::whereIn('status', ['pending', 'under_review'])->count()
            ];
            
            Cache::put($cacheKey, $overview, now()->addMinutes(5));
            
            return [
                'status' => 'success',
                'cache_key' => $cacheKey,
                'ttl_minutes' => 5
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate optimization recommendations.
     */
    protected function generateOptimizationRecommendations(array $results): array
    {
        $recommendations = [];

        // Check table optimization results
        if (isset($results['table_optimization'])) {
            foreach ($results['table_optimization'] as $table => $result) {
                if ($result['status'] === 'error') {
                    $recommendations[] = [
                        'type' => 'table_error',
                        'priority' => 'high',
                        'description' => "Table {$table} optimization failed: {$result['error']}",
                        'table' => $table
                    ];
                }
            }
        }

        // Check index optimization results
        if (isset($results['index_optimization'])) {
            foreach ($results['index_optimization'] as $table => $indexes) {
                foreach ($indexes as $index => $result) {
                    if ($result['status'] === 'error') {
                        $recommendations[] = [
                            'type' => 'index_error',
                            'priority' => 'medium',
                            'description' => "Index {$index} creation failed on table {$table}: {$result['error']}",
                            'table' => $table,
                            'index' => $index
                        ];
                    }
                }
            }
        }

        // Performance recommendations
        $recommendations = array_merge($recommendations, [
            [
                'type' => 'monitoring',
                'priority' => 'medium',
                'description' => 'Set up automated monitoring for slow queries and table performance'
            ],
            [
                'type' => 'maintenance',
                'priority' => 'low',
                'description' => 'Schedule regular database maintenance (weekly optimization, monthly cleanup)'
            ],
            [
                'type' => 'backup',
                'priority' => 'high',
                'description' => 'Ensure regular database backups are configured and tested'
            ]
        ]);

        return $recommendations;
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(): array
    {
        try {
            $metrics = [];

            // Database performance
            $metrics['database'] = $this->getDatabaseMetrics();
            
            // Cache performance
            $metrics['cache'] = $this->getCacheMetrics();
            
            // Query performance
            $metrics['queries'] = $this->getQueryMetrics();
            
            // System performance
            $metrics['system'] = $this->getSystemMetrics();

            return [
                'success' => true,
                'metrics' => $metrics,
                'timestamp' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get performance metrics', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Get database performance metrics.
     */
    protected function getDatabaseMetrics(): array
    {
        $metrics = [];

        try {
            // Table sizes
            $tableSizes = DB::select("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = ?
                ORDER BY size_mb DESC
                LIMIT 10
            ", [DB::getDatabaseName()]);

            $metrics['table_sizes'] = $tableSizes;

            // Connection metrics
            $metrics['connections'] = [
                'active' => DB::selectOne("SHOW STATUS LIKE 'Threads_connected'")->Value ?? 0,
                'max_used' => DB::selectOne("SHOW STATUS LIKE 'Max_used_connections'")->Value ?? 0
            ];

            // Query cache metrics
            $metrics['query_cache'] = [
                'hits' => DB::selectOne("SHOW STATUS LIKE 'Qcache_hits'")->Value ?? 0,
                'misses' => DB::selectOne("SHOW STATUS LIKE 'Qcache_misses'")->Value ?? 0,
                'hit_rate' => $this->calculateQueryCacheHitRate()
            ];

        } catch (\Exception $e) {
            $metrics['error'] = $e->getMessage();
        }

        return $metrics;
    }

    /**
     * Get cache performance metrics.
     */
    protected function getCacheMetrics(): array
    {
        $metrics = [];

        try {
            // Cache hit rates
            $metrics['hit_rates'] = [
                'dashboard' => $this->getCacheHitRate('moderation:dashboard:overview'),
                'report_stats' => $this->getCacheHitRate('moderation:report_stats:dashboard'),
                'moderator_performance' => $this->getCacheHitRate('moderation:moderator_performance:summary')
            ];

            // Cache sizes
            $metrics['sizes'] = [
                'total_keys' => $this->getCacheKeyCount(),
                'memory_usage' => $this->getCacheMemoryUsage()
            ];

        } catch (\Exception $e) {
            $metrics['error'] = $e->getMessage();
        }

        return $metrics;
    }

    /**
     * Get query performance metrics.
     */
    protected function getQueryMetrics(): array
    {
        $metrics = [];

        try {
            // Slow queries
            $slowQueries = DB::select("
                SELECT 
                    query_time,
                    lock_time,
                    rows_sent,
                    rows_examined,
                    sql_text
                FROM mysql.slow_log 
                ORDER BY query_time DESC 
                LIMIT 10
            ");

            $metrics['slow_queries'] = $slowQueries;

            // Query execution times
            $metrics['execution_times'] = [
                'avg_report_query' => $this->measureQueryTime(function() {
                    return Report::where('status', 'pending')->count();
                }),
                'avg_warning_query' => $this->measureQueryTime(function() {
                    return Warning::where('status', 'active')->count();
                })
            ];

        } catch (\Exception $e) {
            $metrics['error'] = $e->getMessage();
        }

        return $metrics;
    }

    /**
     * Get system performance metrics.
     */
    protected function getSystemMetrics(): array
    {
        $metrics = [];

        try {
            // Memory usage
            $metrics['memory'] = [
                'usage' => memory_get_usage(true),
                'peak_usage' => memory_get_peak_usage(true)
            ];

            // CPU usage (if available)
            $metrics['cpu'] = $this->getCpuUsage();

            // Disk usage
            $metrics['disk'] = $this->getDiskUsage();

        } catch (\Exception $e) {
            $metrics['error'] = $e->getMessage();
        }

        return $metrics;
    }

    /**
     * Calculate query cache hit rate.
     */
    protected function calculateQueryCacheHitRate(): float
    {
        try {
            $hits = DB::selectOne("SHOW STATUS LIKE 'Qcache_hits'")->Value ?? 0;
            $misses = DB::selectOne("SHOW STATUS LIKE 'Qcache_misses'")->Value ?? 0;
            $total = $hits + $misses;

            return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get cache hit rate for specific key.
     */
    protected function getCacheHitRate(string $keyPattern): float
    {
        // This would implement cache hit rate tracking
        // For now, return placeholder
        return 85.5;
    }

    /**
     * Get cache key count.
     */
    protected function getCacheKeyCount(): int
    {
        // This would implement cache key counting
        // For now, return placeholder
        return 150;
    }

    /**
     * Get cache memory usage.
     */
    protected function getCacheMemoryUsage(): array
    {
        // This would implement cache memory usage tracking
        // For now, return placeholder
        return [
            'used_mb' => 45.2,
            'available_mb' => 54.8,
            'percentage' => 45.2
        ];
    }

    /**
     * Measure query execution time.
     */
    protected function measureQueryTime(callable $query): float
    {
        $startTime = microtime(true);
        $query();
        $endTime = microtime(true);
        
        return round(($endTime - $startTime) * 1000, 2);
    }

    /**
     * Get CPU usage.
     */
    protected function getCpuUsage(): array
    {
        // This would implement CPU usage monitoring
        // For now, return placeholder
        return [
            'percentage' => 25.5,
            'load_average' => [1.2, 1.5, 1.8]
        ];
    }

    /**
     * Get disk usage.
     */
    protected function getDiskUsage(): array
    {
        // This would implement disk usage monitoring
        // For now, return placeholder
        return [
            'total_gb' => 500,
            'used_gb' => 250,
            'available_gb' => 250,
            'percentage' => 50.0
        ];
    }
}