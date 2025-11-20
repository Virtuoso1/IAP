<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AboutController;
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
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dash', function () {
        return view('dashboard');
    })->name('dashboard');
    
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});



// Home page
Route::get('/', [HomeController::class, 'index'])->name('home');

// Dashboard page
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Admin page
Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');

// About Us page
Route::get('/about', [AboutController::class, 'index'])->name('about');
