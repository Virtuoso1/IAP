<?php

namespace App\Jobs;

use App\Services\AuditLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AuditIntegrityCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $retryAfter = 60;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private ?int $limit = null,
        private bool $forceNotification = false
    ) {
        $this->onQueue('moderation');
    }

    /**
     * Execute the job.
     */
    public function handle(AuditLogService $auditService): void
    {
        try {
            Log::info('Starting audit integrity check', [
                'limit' => $this->limit,
                'force_notification' => $this->forceNotification,
            ]);

            $result = $auditService->verifyBatchIntegrity($this->limit);

            // Log results
            if ($result['verification_passed']) {
                Log::info('Audit integrity check passed', $result);
            } else {
                Log::error('Audit integrity check failed', $result);
                
                // Send alert if integrity violations found
                $this->sendIntegrityAlert($result);
            }

            // Store check results for monitoring
            $this->storeCheckResults($result);

        } catch (\Exception $e) {
            Log::error('Audit integrity check job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'limit' => $this->limit,
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Audit integrity check job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
        ]);

        // Send alert about job failure
        $this->sendJobFailureAlert($exception);
    }

    /**
     * Send integrity alert if violations found.
     */
    protected function sendIntegrityAlert(array $result): void
    {
        try {
            // This would integrate with your alert system
            Log::alert('Audit integrity violations detected - immediate attention required', [
                'total_logs_checked' => $result['total_logs_checked'],
                'violations_found' => $result['violations_found'],
                'integrity_score' => $result['integrity_score'],
                'last_verified_hash' => $result['last_verified_hash'],
                'checked_at' => $result['checked_at'],
            ]);

            // TODO: Implement actual alert system
            // - Send email to administrators
            // - Send push notifications to senior moderators
            // - Create system alerts
            // - Trigger incident response procedures

        } catch (\Exception $e) {
            Log::error('Failed to send integrity alert', [
                'error' => $e->getMessage(),
                'result' => $result,
            ]);
        }
    }

    /**
     * Send job failure alert.
     */
    protected function sendJobFailureAlert(\Throwable $exception): void
    {
        try {
            Log::alert('Audit integrity check job failed - system administrator attention required', [
                'error' => $exception->getMessage(),
                'attempts' => $this->attempts(),
                'max_tries' => $this->tries,
                'queue' => $this->queue,
            ]);

            // TODO: Implement actual alert system
            // - Send email to system administrators
            // - Create high-priority system alert
            // - Trigger monitoring alerts

        } catch (\Exception $e) {
            Log::error('Failed to send job failure alert', [
                'error' => $e->getMessage(),
                'original_exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Store check results for monitoring and reporting.
     */
    protected function storeCheckResults(array $result): void
    {
        try {
            // Store in monitoring database or cache
            Cache::put('audit_integrity_last_check', $result, now()->addHours(24));
            
            // Store historical data
            DB::table('audit_integrity_history')->insert([
                'check_date' => now(),
                'total_logs_checked' => $result['total_logs_checked'],
                'violations_found' => $result['violations_found'],
                'integrity_score' => $result['integrity_score'],
                'last_verified_hash' => $result['last_verified_hash'],
                'verification_passed' => $result['verification_passed'],
                'check_duration_ms' => $result['check_duration_ms'] ?? null,
                'details' => json_encode($result),
            ]);

            Log::debug('Audit integrity check results stored', [
                'integrity_score' => $result['integrity_score'],
                'violations_found' => $result['violations_found'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store audit integrity check results', [
                'error' => $e->getMessage(),
                'result' => $result,
            ]);
        }
    }

    /**
     * Get the display name for the job.
     */
    public function displayName(): string
    {
        return 'Audit Integrity Check';
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['moderation', 'audit', 'integrity-check'];
    }
}