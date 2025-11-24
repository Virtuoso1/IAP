<?php

namespace App\Providers;

use App\Services\Contracts\EvidenceCollectionServiceInterface;
use App\Services\EvidenceCollectionService;
use App\Services\AuditLogService;
use App\Models\Report;
use App\Models\Warning;
use App\Models\UserRestriction;
use App\Models\ModerationAuditLog;
use Illuminate\Support\ServiceProvider;

class ModerationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(EvidenceCollectionServiceInterface::class, function ($app) {
            return new EvidenceCollectionService();
        });

        $this->app->singleton(AuditLogService::class, function ($app) {
            return new AuditLogService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register middleware
        $this->registerMiddleware();
        
        // Register observers
        $this->registerObservers();
        
        // Register event listeners
        $this->registerEventListeners();
    }

    /**
     * Register moderation middleware.
     */
    private function registerMiddleware(): void
    {
        $router = $this->app['router'];
        
        // Register moderation rate limiting middleware
        $router->aliasMiddleware('moderation.throttle', \App\Http\Middleware\ModerationThrottleMiddleware::class);
        
        // Register moderator authentication middleware
        $router->aliasMiddleware('moderator.auth', \App\Http\Middleware\ModeratorAuthMiddleware::class);
        
        // Register evidence validation middleware
        $router->aliasMiddleware('evidence.validation', \App\Http\Middleware\EvidenceValidationMiddleware::class);
    }

    /**
     * Register model observers.
     */
    private function registerObservers(): void
    {
        Report::observe(\App\Observers\ReportObserver::class);
        Warning::observe(\App\Observers\WarningObserver::class);
        UserRestriction::observe(\App\Observers\UserRestrictionObserver::class);
        ModerationAuditLog::observe(\App\Observers\ModerationAuditLogObserver::class);
    }

    /**
     * Register event listeners.
     */
    private function registerEventListeners(): void
    {
        $this->app['events']->listen([
            'report.submitted',
            'report.resolved',
            'warning.issued',
            'restriction.applied',
            'appeal.submitted',
            'appeal.reviewed'
        ], [
            \App\Listeners\LogModerationActivity::class,
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            EvidenceCollectionServiceInterface::class,
            AuditLogService::class,
        ];
    }
}