<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PvDashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [PvDashboardController::class, 'index'])->name('home');

// PV Dashboard Routes
Route::get('/dashboard', [PvDashboardController::class, 'index'])->name('pv.dashboard');
Route::prefix('debug')->group(function () {
    Route::get('/timezone', function () {
        $latest = \App\Models\PvData::latest()->first();
        
        return response()->json([
            'app_timezone' => config('app.timezone'),
            'server_timezone' => date_default_timezone_get(),
            'now_app' => now()->format('Y-m-d H:i:s e'),
            'now_utc' => now('UTC')->format('Y-m-d H:i:s e'),
            'latest_data' => $latest ? [
                'id' => $latest->id,
                'created_at_app' => $latest->created_at->format('Y-m-d H:i:s e'),
                'created_at_utc' => $latest->created_at->setTimezone('UTC')->format('Y-m-d H:i:s e'),
                'timestamp' => $latest->created_at->timestamp,
            ] : null,
        ]);
    });
    
    Route::get('/data-raw', function () {
        $records = \App\Models\PvData::orderByDesc('created_at')->limit(20)->get();
        
        return response()->json([
            'count' => $records->count(),
            'timezone_config' => config('app.timezone'),
            'records' => $records->map(function ($item) {
                return [
                    'id' => $item->id,
                    'created_at_display' => $item->created_at->format('Y-m-d H:i:s'),
                    'created_at_app_tz' => $item->created_at->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s (e)'),
                    'created_at_utc' => $item->created_at->setTimezone('UTC')->format('Y-m-d H:i:s (e)'),
                    'voltage' => $item->voltage,
                    'lux' => $item->lux,
                ];
            })->toArray(),
        ]);
    });
    
    Route::get('/simple-test', function () {
        // Get all data
        $allCount = \App\Models\PvData::count();
        
        // Get data with static dates for testing
        $start = \Carbon\Carbon::parse('2026-05-18 14:00:00');  // App timezone
        $end = \Carbon\Carbon::parse('2026-05-18 23:59:59');    // App timezone
        
        $dataWithRange = \App\Models\PvData::whereBetween('created_at', [$start, $end])->get();
        
        // Get latest
        $latest = \App\Models\PvData::latest()->first();
        
        return response()->json([
            'count_all' => $allCount,
            'count_with_range' => $dataWithRange->count(),
            'range_start' => $start->format('Y-m-d H:i:s e'),
            'range_end' => $end->format('Y-m-d H:i:s e'),
            'latest_id' => $latest?->id,
            'latest_created_at' => $latest?->created_at?->format('Y-m-d H:i:s e'),
            'latest_created_at_utc' => $latest?->created_at?->setTimezone('UTC')->format('Y-m-d H:i:s e'),
            'sample_range_data' => $dataWithRange->take(3)->map(function($item) {
                return [
                    'id' => $item->id,
                    'created_at' => $item->created_at->format('Y-m-d H:i:s e'),
                ];
            })->toArray(),
        ]);
    });
