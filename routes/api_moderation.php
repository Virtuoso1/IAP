<?php

use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\WarningController;
use App\Http\Controllers\Api\UserRestrictionController;
use App\Http\Controllers\Api\AppealController;
use App\Http\Controllers\Api\ModerationDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Moderation API Routes
|--------------------------------------------------------------------------
|
| These routes are typically stateless and require API authentication.
|
*/

// Public GET routes for reports and appeals (no auth required) - MUST BE DEFINED FIRST
Route::get('/reports', [ReportController::class, 'publicIndex'])->name('api.reports.public');
Route::get('/appeals', [AppealController::class, 'publicIndex'])->name('api.appeals.public');

Route::middleware(['auth', 'moderator.auth', 'moderation.throttle:60,1'])->prefix('moderation')->group(function () {
    
    // Report Management Routes
    Route::prefix('reports')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('api.moderation.reports.index');
        Route::post('/', [ReportController::class, 'store'])->name('api.moderation.reports.store');
        Route::get('/categories', [ReportController::class, 'categories'])->name('api.moderation.reports.categories');
        Route::get('/statistics', [ReportController::class, 'statistics'])->name('api.moderation.reports.statistics');
        
        Route::prefix('{report}')->group(function () {
            Route::get('/', [ReportController::class, 'show'])->name('api.moderation.reports.show');
            Route::put('/', [ReportController::class, 'update'])->name('api.moderation.reports.update');
            Route::delete('/', [ReportController::class, 'destroy'])->name('api.moderation.reports.destroy');
            Route::post('/resolve', [ReportController::class, 'resolve'])->name('api.moderation.reports.resolve');
        });
    });

    // Warning Management Routes
    Route::prefix('warnings')->group(function () {
        Route::get('/', [WarningController::class, 'index'])->name('api.moderation.warnings.index');
        Route::post('/', [WarningController::class, 'store'])->name('api.moderation.warnings.store');
        Route::get('/statistics', [WarningController::class, 'statistics'])->name('api.moderation.warnings.statistics');
        
        Route::prefix('{warning}')->group(function () {
            Route::get('/', [WarningController::class, 'show'])->name('api.moderation.warnings.show');
            Route::put('/', [WarningController::class, 'update'])->name('api.moderation.warnings.update');
            Route::delete('/', [WarningController::class, 'destroy'])->name('api.moderation.warnings.destroy');
            Route::post('/escalate', [WarningController::class, 'escalate'])->name('api.moderation.warnings.escalate');
            Route::post('/acknowledge', [WarningController::class, 'acknowledge'])->name('api.moderation.warnings.acknowledge');
        });
    });

    // User Restriction Management Routes
    Route::prefix('restrictions')->group(function () {
        Route::get('/', [UserRestrictionController::class, 'index'])->name('api.moderation.restrictions.index');
        Route::post('/', [UserRestrictionController::class, 'store'])->name('api.moderation.restrictions.store');
        Route::get('/types', [UserRestrictionController::class, 'types'])->name('api.moderation.restrictions.types');
        Route::get('/statistics', [UserRestrictionController::class, 'statistics'])->name('api.moderation.restrictions.statistics');
        
        Route::prefix('{restriction}')->group(function () {
            Route::get('/', [UserRestrictionController::class, 'show'])->name('api.moderation.restrictions.show');
            Route::put('/', [UserRestrictionController::class, 'update'])->name('api.moderation.restrictions.update');
            Route::delete('/', [UserRestrictionController::class, 'destroy'])->name('api.moderation.restrictions.destroy');
            Route::post('/lift', [UserRestrictionController::class, 'lift'])->name('api.moderation.restrictions.lift');
            Route::post('/extend', [UserRestrictionController::class, 'extend'])->name('api.moderation.restrictions.extend');
        });
    });

    // Appeal Management Routes
    Route::prefix('appeals')->group(function () {
        Route::get('/', [AppealController::class, 'index'])->name('api.moderation.appeals.index');
        Route::post('/', [AppealController::class, 'store'])->name('api.moderation.appeals.store');
        Route::get('/statistics', [AppealController::class, 'statistics'])->name('api.moderation.appeals.statistics');
        
        Route::prefix('{appeal}')->group(function () {
            Route::get('/', [AppealController::class, 'show'])->name('api.moderation.appeals.show');
            Route::put('/', [AppealController::class, 'update'])->name('api.moderation.appeals.update');
            Route::post('/review', [AppealController::class, 'review'])->name('api.moderation.appeals.review');
            Route::post('/approve', [AppealController::class, 'approve'])->name('api.moderation.appeals.approve');
            Route::post('/deny', [AppealController::class, 'deny'])->name('api.moderation.appeals.deny');
        });
    });

    // Dashboard Routes
    Route::prefix('dashboard')->group(function () {
        Route::get('/overview', [ModerationDashboardController::class, 'overview'])->name('api.moderation.dashboard.overview');
        Route::get('/activity', [ModerationDashboardController::class, 'activity'])->name('api.moderation.dashboard.activity');
        Route::get('/performance', [ModerationDashboardController::class, 'performance'])->name('api.moderation.dashboard.performance');
        Route::get('/queue', [ModerationDashboardController::class, 'queue'])->name('api.moderation.dashboard.queue');
        Route::get('/alerts', [ModerationDashboardController::class, 'alerts'])->name('api.moderation.dashboard.alerts');
    });

    // Evidence Management Routes
    Route::prefix('evidence')->group(function () {
        Route::get('/', [ReportController::class, 'evidenceIndex'])->name('api.moderation.evidence.index');
        Route::post('/upload', [ReportController::class, 'uploadEvidence'])->name('api.moderation.evidence.upload');
        Route::get('/{evidence}', [ReportController::class, 'showEvidence'])->name('api.moderation.evidence.show');
        Route::delete('/{evidence}', [ReportController::class, 'deleteEvidence'])->name('api.moderation.evidence.destroy');
    });

    // Audit Routes
    Route::prefix('audit')->group(function () {
        Route::get('/logs', [ModerationDashboardController::class, 'auditLogs'])->name('api.moderation.audit.logs');
        Route::get('/integrity', [ModerationDashboardController::class, 'integrityCheck'])->name('api.moderation.audit.integrity');
        Route::post('/integrity/verify', [ModerationDashboardController::class, 'verifyIntegrity'])->name('api.moderation.audit.integrity.verify');
    });
});

// Moderation info endpoint
Route::get('/moderation', function () {
    return response()->json([
        'message' => 'Moderation API is working',
        'endpoints' => [
            'reports' => '/api/moderation/reports',
            'appeals' => '/api/moderation/appeals',
            'warnings' => '/api/moderation/warnings',
            'restrictions' => '/api/moderation/restrictions',
            'dashboard' => '/api/moderation/dashboard/*',
            'evidence' => '/api/moderation/evidence/*',
            'audit' => '/api/moderation/audit/*'
        ]
    ]);
});

// Public Reporting Routes (no moderator auth required)
Route::middleware(['auth', 'throttle:5,1'])->prefix('reports')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('api.reports.index');
    Route::post('/', [ReportController::class, 'store'])->name('api.reports.store');
    Route::get('/my-reports', [ReportController::class, 'myReports'])->name('api.reports.my');
    Route::get('/categories', [ReportController::class, 'categories'])->name('api.reports.categories');
});

// Appeal Routes for Users (no moderator auth required)
Route::middleware(['auth', 'throttle:3,1'])->prefix('appeals')->group(function () {
    Route::get('/', [AppealController::class, 'index'])->name('api.appeals.index');
    Route::get('/my-appeals', [AppealController::class, 'myAppeals'])->name('api.appeals.my');
    Route::post('/', [AppealController::class, 'store'])->name('api.appeals.store');
    Route::get('/{appeal}', [AppealController::class, 'show'])->name('api.appeals.show');
});