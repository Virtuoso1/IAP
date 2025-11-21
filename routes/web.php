<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\MessageController;
Route::get('/', function () {
    return view('welcome');
});

// Guest routes (not logged in)
Route::middleware('guest')->group(function () {
    // Registration
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    
    // Login
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/messages', [MessageController::class, 'inbox']);
    Route::get('/messages/{user}', [MessageController::class, 'conversation']);
    Route::post('/messages/send', [MessageController::class, 'send']);
    Route::post('/users/{user}/block', [MessageController::class, 'block']);
    Route::delete('/users/{user}/unblock', [MessageController::class, 'unblock']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});



// Home page
Route::get('/', [HomeController::class, 'index'])->name('home');


// Group routes
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupMessageController;
use App\Http\Controllers\GroupUserController;

Route::middleware('auth')->group(function () {
    Route::resource('groups', GroupController::class);
    Route::resource('groups.messages', GroupMessageController::class);
    Route::resource('groups.users', GroupUserController::class)->parameters(['groups' => 'group']);
});

// Admin page
Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');

// About Us page
Route::get('/about', [AboutController::class, 'index'])->name('about');
