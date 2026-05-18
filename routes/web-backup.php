<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PvDashboardController;

Route::get('/', [PvDashboardController::class, 'index'])->name('home');
Route::get('/dashboard', [PvDashboardController::class, 'index'])->name('pv.dashboard');
