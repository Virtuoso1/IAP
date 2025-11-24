<?php

use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\AppealController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes (No Authentication Required)
|--------------------------------------------------------------------------
|
| These routes are publicly accessible without authentication.
|
*/

// Public GET routes for reports and appeals (no auth required)
Route::get('/reports', [ReportController::class, 'publicIndex'])->name('api.public.reports');
Route::get('/appeals', [AppealController::class, 'publicIndex'])->name('api.public.appeals');

// Public API info endpoint
Route::get('/public-info', function () {
    return response()->json([
        'message' => 'Public API is working',
        'endpoints' => [
            'reports' => '/api/reports',
            'appeals' => '/api/appeals',
        ]
    ]);
});