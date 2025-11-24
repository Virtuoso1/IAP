<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupMessageController;
use App\Http\Controllers\GroupUserController;
use App\Http\Controllers\MatchController;


// Home page
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('about');

// Guest routes (not logged in)
Route::middleware('guest')->group(function () {
    // Registration
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    // Login
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    // Messages for guests (if allowed)
    Route::get('/messages', [MessageController::class, 'inbox']);
    Route::get('/messages/{user}', [MessageController::class, 'conversation']);
    Route::post('/messages/send', [MessageController::class, 'send']);
    Route::post('/users/{user}/block', [MessageController::class, 'block']);
    Route::delete('/users/{user}/unblock', [MessageController::class, 'unblock']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Messages
    Route::get('/messages', [MessageController::class, 'inbox'])->name('messages.inbox');
    Route::get('/messages/{user}', [MessageController::class, 'conversation'])->name('messages.conversation');
    Route::post('/messages/send', [MessageController::class, 'send'])->name('messages.send');
    Route::post('/users/{user}/block', [MessageController::class, 'block'])->name('users.block');
    Route::delete('/users/{user}/unblock', [MessageController::class, 'unblock'])->name('users.unblock');
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});



// Home page
Route::get('/', [HomeController::class, 'index'])->name('home');



// Group routes


Route::middleware('auth')->group(function () {
    // Groups
    Route::resource('groups', GroupController::class);
    Route::post('/groups/{group}/invite', [GroupController::class, 'invite'])->name('groups.invite');
    Route::resource('groups.messages', GroupMessageController::class);
    Route::resource('groups.users', GroupUserController::class)
        ->parameters(['groups' => 'group']);
    // Matches
    Route::prefix('matches')->name('matches.')->group(function () {
        Route::get('/', [MatchController::class, 'index'])->name('index');
        Route::get('/create', [MatchController::class, 'create'])->name('create');
        Route::get('/helpers', [MatchController::class, 'helpers'])->name('helpers');
        Route::get('/pending', [MatchController::class, 'pending'])->name('pending');
        Route::post('/', [MatchController::class, 'store'])->name('store');
        Route::get('/{match}', [MatchController::class, 'show'])->name('show');
        Route::post('/{match}/activate', [MatchController::class, 'activate'])->name('activate');
        Route::post('/{match}/accept', [MatchController::class, 'accept'])->name('accept');
        Route::post('/{match}/complete', [MatchController::class, 'complete'])->name('complete');
        Route::post('/{match}/cancel', [MatchController::class, 'cancel'])->name('cancel');
    });
    // Admin page
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
});

// Admin page
Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');

// About Us page
Route::get('/about', [AboutController::class, 'index'])->name('about');

// Include moderation routes
require __DIR__.'/web_moderation.php';
