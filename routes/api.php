<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PvDashboardController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// PV Dashboard API Routes
Route::prefix('pv')->group(function () {
    // Get latest PV data
    Route::get('/latest', function () {
        $latest = \App\Models\PvData::getLatest();
        $isOffline = \App\Models\PvData::isOffline();
        $lastUpdateTime = $latest ? $latest->updated_at->format('Y-m-d l H:i') : null; // Format: 2026-03-23 Monday 17:15
        
        return response()->json([
            'success' => true,
            'data' => $latest,
            'isOffline' => $isOffline,
            'lastUpdateTime' => $lastUpdateTime,
        ]);
    });

    // Get power output chart data
    Route::get('/power-output', [PvDashboardController::class, 'getPowerOutputData']);

    // Get environment chart data
    Route::get('/environment', [PvDashboardController::class, 'getEnvironmentData']);

    // Get generic parameter chart data
    Route::get('/chart', [PvDashboardController::class, 'getChartData']);

    // Debug endpoint for diagnosing query issues
    Route::get('/debug-chart', function () {
        $allCount = \App\Models\PvData::count();
        $latestRecord = \App\Models\PvData::latest()->first();
        
        // Test 1H query
        $start1h = now()->subHour();
        $end1h = now();
        $startUtc1h = $start1h->copy()->setTimezone('UTC');
        $endUtc1h = $end1h->copy()->setTimezone('UTC');
        $data1h = \App\Models\PvData::whereBetween('created_at', [$startUtc1h, $endUtc1h])->get();
        
        return response()->json([
            'total_count' => $allCount,
            'latest_id' => $latestRecord?->id,
            'latest_created_at_utc' => $latestRecord?->created_at,
            'latest_created_at_app' => $latestRecord?->created_at?->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s e'),
            'test_1h' => [
                'start_app' => $start1h->format('Y-m-d H:i:s e'),
                'end_app' => $end1h->format('Y-m-d H:i:s e'),
                'start_utc' => $startUtc1h->format('Y-m-d H:i:s e'),
                'end_utc' => $endUtc1h->format('Y-m-d H:i:s e'),
                'count' => $data1h->count(),
                'sample' => $data1h->take(3)->map(fn($item) => [
                    'id' => $item->id,
                    'created_at' => $item->created_at,
                ])->toArray(),
            ],
        ]);
    });

    // Store new PV data
    Route::post('/store', [PvDashboardController::class, 'store']);

    // Ingest data from ESP sensor
    Route::post('/ingest', [PvDashboardController::class, 'ingest']);
});
