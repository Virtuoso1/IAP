<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Include moderation API routes
require __DIR__.'/api_moderation.php';

// Basic API info route
Route::get('/info', function (Request $request) {
    return response()->json([
        'application' => 'IAP - Mental Health Support Platform',
        'version' => '1.0.0',
        'status' => 'running',
        'endpoints' => [
            'moderation' => '/api/moderation/*',
            'reports' => '/api/reports/*',
            'appeals' => '/api/appeals/*',
        ]
    ]);
});

// API Login route
Route::post('/login', function (Request $request) {
    $credentials = $request->only('email', 'password');
    
    if (Auth::guard('web')->attempt($credentials)) {
        $user = Auth::guard('web')->user();
        
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'username' => $user->username,
                    'role' => $user->role,
                    'is_moderator' => $user->role === 'moderator' || $user->role === 'admin'
                ]
            ]
        ]);
    }
    
    return response()->json([
        'success' => false,
        'message' => 'Invalid credentials'
    ], 401);
})->middleware(['web', 'auth.session'])->withoutMiddleware(['web']);

// Health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'healthy']);
});

// Test moderation endpoint (no auth required)
Route::get('/moderation-test', function () {
    return response()->json([
        'message' => 'Moderation API routes are working!',
        'routes' => [
            'moderation' => '/api/moderation/*',
            'reports' => '/api/reports/*',
            'appeals' => '/api/appeals/*'
        ]
    ]);
});