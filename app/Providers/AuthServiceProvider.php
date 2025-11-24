<?php

namespace App\Providers;

use App\Policies\ReportPolicy;
use App\Policies\AppealPolicy;
use App\Models\Report;
use App\Models\Appeal;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        
        Gate::policy(Report::class, ReportPolicy::class);
        Gate::policy(Appeal::class, AppealPolicy::class);
    }
}