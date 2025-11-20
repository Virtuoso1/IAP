<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AboutController;

// Home page
Route::get('/', [HomeController::class, 'index'])->name('home');

// Dashboard page
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Admin page
Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');

// About Us page
Route::get('/about', [AboutController::class, 'index'])->name('about');
