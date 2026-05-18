<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Verify timezone consistency in pv_data table
     */
    public function up(): void
    {
        // This migration helps diagnose timezone issues
        // It doesn't modify data, just helps verify consistency
        
        // Check if we have any data and log it for debugging
        $count = DB::table('pv_data')->count();
        
        if ($count > 0) {
            $latest = DB::table('pv_data')
                ->orderBy('created_at', 'desc')
                ->first();
            
            \Log::info('PV Data Timezone Check - Migration', [
                'total_records' => $count,
                'latest_created_at' => $latest->created_at,
                'timezone_config' => config('app.timezone'),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse
    }
};
