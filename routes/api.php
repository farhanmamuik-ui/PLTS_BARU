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
        $allRecords = \App\Models\PvData::orderBy('created_at', 'asc')->get();
        $latestRecord = \App\Models\PvData::latest()->first();
        
        // Get date range from database
        $firstRecord = \App\Models\PvData::orderBy('created_at', 'asc')->first();
        
        // Test 24H query
        $start24h = now()->subDay();
        $end24h = now();
        $startUtc24h = $start24h->copy()->setTimezone('UTC');
        $endUtc24h = $end24h->copy()->setTimezone('UTC');
        
        $startTimestamp = $startUtc24h->timestamp;
        $endTimestamp = $endUtc24h->timestamp;
        
        $data24h = \App\Models\PvData::whereRaw("UNIX_TIMESTAMP(created_at) BETWEEN ? AND ?", [$startTimestamp, $endTimestamp])->get();
        
        return response()->json([
            'total_count' => $allCount,
            'first_record' => $firstRecord ? [
                'id' => $firstRecord->id,
                'created_at_utc' => $firstRecord->created_at->format('Y-m-d H:i:s e'),
                'created_at_app' => $firstRecord->created_at->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s e'),
            ] : null,
            'latest_record' => $latestRecord ? [
                'id' => $latestRecord->id,
                'created_at_utc' => $latestRecord->created_at->format('Y-m-d H:i:s e'),
                'created_at_app' => $latestRecord->created_at->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s e'),
            ] : null,
            'test_24h' => [
                'start_app' => $start24h->format('Y-m-d H:i:s e'),
                'end_app' => $end24h->format('Y-m-d H:i:s e'),
                'start_utc' => $startUtc24h->format('Y-m-d H:i:s e'),
                'end_utc' => $endUtc24h->format('Y-m-d H:i:s e'),
                'start_timestamp' => $startTimestamp,
                'end_timestamp' => $endTimestamp,
                'count_returned' => $data24h->count(),
                'records' => $data24h->map(fn($item) => [
                    'id' => $item->id,
                    'created_at_utc' => $item->created_at->format('Y-m-d H:i:s e'),
                    'created_at_app' => $item->created_at->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s e'),
                    'lux' => $item->lux,
                ])->toArray(),
            ],
        ]);
    });

    // Store new PV data
    Route::post('/store', [PvDashboardController::class, 'store']);

    // Ingest data from ESP sensor
    Route::post('/ingest', [PvDashboardController::class, 'ingest']);
});
