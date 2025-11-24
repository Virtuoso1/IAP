<?php

use App\Http\Controllers\ModerationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Moderation Web Routes
|--------------------------------------------------------------------------
|
| These routes provide web interface for moderation features.
|
*/

Route::middleware(['auth', 'verified'])->prefix('moderation')->name('moderation.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [ModerationController::class, 'dashboard'])->name('dashboard');
    
    // Reports
    Route::get('/reports', [ModerationController::class, 'reports'])->name('reports');
    Route::get('/reports/{id}', [ModerationController::class, 'showReport'])->name('reports.show');
    
    // Warnings
    Route::get('/warnings', [ModerationController::class, 'warnings'])->name('warnings');
    Route::get('/warnings/{id}', [ModerationController::class, 'showWarning'])->name('warnings.show');
    
    // User Restrictions
    Route::get('/restrictions', [ModerationController::class, 'restrictions'])->name('restrictions');
    
    // Appeals
    Route::get('/appeals', [ModerationController::class, 'appeals'])->name('appeals');
});