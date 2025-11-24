<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Report;
use App\Models\Warning;
use App\Models\UserRestriction;
use App\Models\ModerationAuditLog;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * The currently authenticated user.
     */
    protected $currentUser;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable detailed error reporting for tests
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Set test timezone
        date_default_timezone_set('UTC');
        
        // Mock external services
        $this->mockExternalServices();
    }

    /**
     * Clean up the test environment.
     */
    protected function tearDown(): void
    {
        // Clean up any test data
        $this->cleanupTestData();
        
        parent::tearDown();
    }

    /**
     * Mock external services for testing.
     */
    protected function mockExternalServices(): void
    {
        // Mock AWS services
        $this->mockAwsServices();
        
        // Mock CDN services
        $this->mockCdnServices();
        
        // Mock Redis cluster
        $this->mockRedisCluster();
        
        // Mock monitoring services
        $this->mockMonitoringServices();
    }

    /**
     * Mock AWS services.
     */
    protected function mockAwsServices(): void
    {
        // Mock CloudFront
        $this->app->instance('aws.cloudfront', $this->createMockCloudFront());
        
        // Mock Route 53
        $this->app->instance('aws.route53', $this->createMockRoute53());
        
        // Mock ElastiCache
        $this->app->instance('aws.elasticache', $this->createMockElastiCache());
        
        // Mock Security Hub
        $this->app->instance('aws.securityhub', $this->createMockSecurityHub());
        
        // Mock KMS
        $this->app->instance('aws.kms', $this->createMockKms());
    }

    /**
     * Mock CDN services.
     */
    protected function mockCdnServices(): void
    {
        $this->app->instance('cdn.driver', $this->createMockCdnDriver());
    }

    /**
     * Mock Redis cluster.
     */
    protected function mockRedisCluster(): void
    {
        $this->app->instance('redis.cluster', $this->createMockRedisCluster());
    }

    /**
     * Mock monitoring services.
     */
    protected function mockMonitoringServices(): void
    {
        $this->app->instance('monitoring.elasticsearch', $this->createMockElasticsearch());
        $this->app->instance('monitoring.grafana', $this->createMockGrafana());
        $this->app->instance('monitoring.prometheus', $this->createMockPrometheus());
        $this->app->instance('monitoring.alertstack', $this->createMockAlertstack());
    }

    /**
     * Create a moderator user for testing.
     */
    protected function createModerator(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Test Moderator',
            'email' => 'moderator@test.com',
            'password' => Hash::make('password'),
            'is_moderator' => true,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Create an admin user for testing.
     */
    protected function createAdmin(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Create a regular user for testing.
     */
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Create a test report.
     */
    protected function createReport(array $attributes = []): Report
    {
        return Report::factory()->create(array_merge([
            'status' => 'pending',
            'priority' => 'medium',
            'reason' => 'Test report reason',
            'reporter_id' => $this->createModerator()->id,
        ], $attributes));
    }

    /**
     * Create a test warning.
     */
    protected function createWarning(array $attributes = []): Warning
    {
        return Warning::factory()->create(array_merge([
            'type' => 'formal',
            'level' => 1,
            'reason' => 'Test warning reason',
            'user_id' => $this->createUser()->id,
            'moderator_id' => $this->createModerator()->id,
            'status' => 'active',
        ], $attributes));
    }

    /**
     * Create a test user restriction.
     */
    protected function createUserRestriction(array $attributes = []): UserRestriction
    {
        return UserRestriction::factory()->create(array_merge([
            'type' => 'message_mute',
            'reason' => 'Test restriction reason',
            'user_id' => $this->createUser()->id,
            'moderator_id' => $this->createModerator()->id,
            'is_active' => true,
            'is_permanent' => false,
            'expires_at' => now()->addDays(7),
        ], $attributes));
    }

    /**
     * Create a test audit log.
     */
    protected function createAuditLog(array $attributes = []): ModerationAuditLog
    {
        return ModerationAuditLog::factory()->create(array_merge([
            'entity_type' => 'Report',
            'entity_id' => 1,
            'action' => 'test_action',
            'actor_id' => $this->createModerator()->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ], $attributes));
    }

    /**
     * Clean up test data.
     */
    protected function cleanupTestData(): void
    {
        // Clean up any temporary files
        $tempFiles = glob(storage_path('app/testing/*'));
        foreach ($tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // Clear test cache
        $this->artisan('cache:clear');
        
        // Reset test database
        $this->artisan('migrate:fresh', ['--seed' => false]);
    }

    /**
     * Assert audit log integrity.
     */
    protected function assertAuditLogIntegrity(ModerationAuditLog $auditLog): void
    {
        $data = $auditLog->toArray();
        unset($data['hash']);
        
        $calculatedHash = hash('sha256', json_encode($data, 64)); // 64 = JSON_SORT_KEYS
        $this->assertEquals($auditLog->hash, $calculatedHash, 'Audit log integrity check failed');
    }

    /**
     * Assert cache hit rate.
     */
    protected function assertCacheHitRate(string $cacheKey, float $expectedRate): void
    {
        $cacheStats = $this->app->make('cache.store')->getStats();
        $actualRate = $cacheStats['hits'] / ($cacheStats['hits'] + $cacheStats['misses']);
        
        $this->assertEqualsWithDelta($expectedRate, $actualRate, 0.05, "Cache hit rate for {$cacheKey}");
    }

    /**
     * Assert response time SLA.
     */
    protected function assertResponseTimeSla(callable $operation, float $maxTimeMs): void
    {
        $startTime = microtime(true);
        $result = $operation();
        $endTime = microtime(true);
        
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $this->assertLessThanOrEqual($maxTimeMs, $responseTime, 
            "Response time {$responseTime}ms exceeds SLA of {$maxTimeMs}ms");
    }

    /**
     * Assert security compliance.
     */
    protected function assertSecurityCompliance(array $requirements): void
    {
        foreach ($requirements as $requirement => $expected) {
            $actual = $this->app->make('security.compliance')->check($requirement);
            $this->assertEquals($expected, $actual, "Security requirement {$requirement} not met");
        }
    }

    /**
     * Mock CloudFront service.
     */
    protected function createMockCloudFront()
    {
        return \Mockery::mock('App\Services\Cdn\CloudFrontService');
    }

    /**
     * Mock Route 53 service.
     */
    protected function createMockRoute53()
    {
        return \Mockery::mock('App\Services\Dns\Route53Service');
    }

    /**
     * Mock ElastiCache service.
     */
    protected function createMockElastiCache()
    {
        return \Mockery::mock('App\Services\Cache\ElastiCacheService');
    }

    /**
     * Mock Security Hub service.
     */
    protected function createMockSecurityHub()
    {
        return \Mockery::mock('App\Services\Security\SecurityHubService');
    }

    /**
     * Mock KMS service.
     */
    protected function createMockKms()
    {
        return \Mockery::mock('App\Services\Encryption\KmsService');
    }

    /**
     * Mock CDN driver.
     */
    protected function createMockCdnDriver()
    {
        return \Mockery::mock('App\Services\Cdn\CdnDriverInterface');
    }

    /**
     * Mock Redis cluster.
     */
    protected function createMockRedisCluster()
    {
        return \Mockery::mock('App\Services\Cache\RedisClusterService');
    }

    /**
     * Mock Elasticsearch service.
     */
    protected function createMockElasticsearch()
    {
        return \Mockery::mock('App\Services\Monitoring\ElasticsearchService');
    }

    /**
     * Mock Grafana service.
     */
    protected function createMockGrafana()
    {
        return \Mockery::mock('App\Services\Monitoring\GrafanaService');
    }

    /**
     * Mock Prometheus service.
     */
    protected function createMockPrometheus()
    {
        return \Mockery::mock('App\Services\Monitoring\PrometheusService');
    }

    /**
     * Mock Alertstack service.
     */
    protected function createMockAlertstack()
    {
        return \Mockery::mock('App\Services\Monitoring\AlertstackService');
    }
}
