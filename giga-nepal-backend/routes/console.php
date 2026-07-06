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
use Illuminate\Support\Facades\Schedule;

Schedule::job(new DetectAbandonedCartsJob)->everyFifteenMinutes();
Schedule::job(new CalculateTrendingProductsJob)->hourly();
Schedule::job(new CalculateTrendingCategoriesJob)->hourly();
Schedule::job(new CalculateTopSearchTermsJob)->hourly();
Schedule::job(new RefreshCustomerSegmentJob)->daily();
Schedule::job(new GenerateRegionalSalesReportJob)->daily();
