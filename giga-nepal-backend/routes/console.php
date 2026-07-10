<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Marketing Phase 2 scheduler. Jobs are queue-safe placeholders until providers are enabled.
use App\Jobs\Marketing\CalculateTopSearchTermsJob;
use App\Jobs\Marketing\CalculateTrendingCategoriesJob;
use App\Jobs\Marketing\CalculateTrendingProductsJob;
use App\Jobs\Marketing\DetectAbandonedCartsJob;
use App\Jobs\Marketing\GenerateRegionalSalesReportJob;
use App\Jobs\Marketing\RefreshCustomerSegmentJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('neogiga:smoke', function () {
    $failed = false;

    $check = function (string $name, callable $callback) use (&$failed): void {
        try {
            $result = (bool) $callback();
            $this->line(($result ? 'PASS' : 'FAIL') . ' ' . $name);
            $failed = $failed || ! $result;
        } catch (Throwable $exception) {
            $this->line('FAIL ' . $name . ' - ' . $exception->getMessage());
            $failed = true;
        }
    };

    $check('app key configured', fn (): bool => filled(config('app.key')));
    $check('database ping', fn (): bool => DB::select('select 1 as health_check') !== []);
    $check('cache write/read', function (): bool {
        $key = 'neogiga:smoke:' . app()->environment();
        Cache::put($key, 'ok', 60);

        return Cache::get($key) === 'ok';
    });
    $check('jobs table visible', fn (): bool => config('queue.default') !== 'database' || Schema::hasTable('jobs'));
    $check('storage framework writable', fn (): bool => is_writable(storage_path('framework')));
    $check('bootstrap cache writable', fn (): bool => is_writable(base_path('bootstrap/cache')));

    return $failed ? self::FAILURE : self::SUCCESS;
})->purpose('Run production-safe NeoGiga smoke checks without migrations or data changes.');

Schedule::job(new DetectAbandonedCartsJob)->everyFifteenMinutes();
Schedule::job(new CalculateTrendingProductsJob)->hourly();
Schedule::job(new CalculateTrendingCategoriesJob)->hourly();
Schedule::job(new CalculateTopSearchTermsJob)->hourly();
Schedule::job(new RefreshCustomerSegmentJob)->daily();
Schedule::job(new GenerateRegionalSalesReportJob)->daily();

// Inventory reservation cleanup - runs every minute to release expired 15-minute reservations
Schedule::job(new \App\Jobs\ProcessStockReservation())->everyMinute();
