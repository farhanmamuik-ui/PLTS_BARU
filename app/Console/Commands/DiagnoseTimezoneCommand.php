<?php

namespace App\Console\Commands;

use App\Models\PvData;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DiagnoseTimezoneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pv:diagnose-timezone {--show-raw : Tampilkan raw data} {--fix : Fix timezone issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose and fix timezone issues in PV data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== PV Data Timezone Diagnosis ===');
        $this->newLine();
        
        $this->info('Configuration:');
        $this->line('  App Timezone: ' . config('app.timezone'));
        $this->line('  Server Timezone: ' . date_default_timezone_get());
        $this->line('  Current Time (App TZ): ' . now()->format('Y-m-d H:i:s e'));
        $this->line('  Current Time (UTC): ' . now('UTC')->format('Y-m-d H:i:s e'));
        $this->newLine();
        
        // Check data
        $count = PvData::count();
        $this->info("Database Records: $count");
        
        if ($count === 0) {
            $this->warn('No data in database yet.');
            return 0;
        }
        
        $latest = PvData::latest()->first();
        $oldest = PvData::oldest()->first();
        
        $this->info('Data Range:');
        $this->line('  Latest: ' . $latest->created_at->format('Y-m-d H:i:s'));
        $this->line('  Oldest: ' . $oldest->created_at->format('Y-m-d H:i:s'));
        $this->newLine();
        
        // Show test queries
        $this->info('Test Queries:');
        $last1h = PvData::whereBetween('created_at', [
            now()->subHour(),
            now()
        ])->count();
        $this->line("  Records in last 1 hour: $last1h");
        
        $last24h = PvData::whereBetween('created_at', [
            now()->subDay(),
            now()
        ])->count();
        $this->line("  Records in last 24 hours: $last24h");
        $this->newLine();
        
        // Show sample records
        if ($this->option('show-raw')) {
            $this->showRawData();
        }
        
        // Apply fix if requested
        if ($this->option('fix')) {
            $this->fixTimezoneIssues();
        }
        
        $this->info('=== Diagnosis Complete ===');
        return 0;
    }
    
    private function showRawData(): void
    {
        $this->info('Last 10 Records (with timezone info):');
        $this->newLine();
        
        $records = PvData::latest()->limit(10)->get();
        
        foreach ($records as $record) {
            $this->line(sprintf(
                'ID: %d | App TZ: %s | UTC: %s | Lux: %.2f',
                $record->id,
                $record->created_at->format('Y-m-d H:i:s'),
                $record->created_at->setTimezone('UTC')->format('Y-m-d H:i:s'),
                $record->lux
            ));
        }
        $this->newLine();
    }
    
    private function fixTimezoneIssues(): void
    {
        $this->warn('Timezone fix feature is currently disabled.');
        $this->line('For production data, please contact administrator.');
        $this->line('The recent code changes should handle new data correctly.');
    }
}
