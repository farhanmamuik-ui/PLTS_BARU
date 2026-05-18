<?php

use Illuminate\Support\Facades\Route;
use App\Models\PvData;
use Illuminate\Support\Carbon;

// Debug route - hapus setelah diagnosa selesai
Route::get('/debug/timezone', function () {
    $latest = PvData::latest()->first();
    
    return response()->json([
        'app_timezone' => config('app.timezone'),
        'server_timezone' => date_default_timezone_get(),
        'now_app' => now()->format('Y-m-d H:i:s e'),
        'latest_data' => $latest ? [
            'created_at_raw' => $latest->created_at,
            'created_at_formatted' => $latest->created_at->format('Y-m-d H:i:s e'),
            'timestamp' => $latest->created_at->timestamp,
        ] : null,
        '1h_ago' => now()->subHour()->format('Y-m-d H:i:s e'),
        '24h_ago' => now()->subDay()->format('Y-m-d H:i:s e'),
        
        // Test query untuk 1 jam terakhir
        'data_last_1h_count' => PvData::whereBetween('created_at', [
            now()->subHour(),
            now()
        ])->count(),
        
        // Tampilkan beberapa record terakhir
        'last_5_records' => PvData::latest()->limit(5)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                'lux' => $item->lux,
                'voltage' => $item->voltage,
            ];
        })->toArray(),
    ]);
});
