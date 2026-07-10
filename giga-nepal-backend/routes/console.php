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

Artisan::command('jlcpcb:repair-skus {--apply : Persist SKU changes. Without this flag the command is a dry-run.} {--limit=0 : Maximum rows to process, 0 means all.}', function () {
    if (! Schema::hasTable('catalog_product_sources') || ! Schema::hasTable('catalog_sources') || ! Schema::hasTable('products')) {
        $this->error('Required catalog source/product tables are missing.');

        return self::FAILURE;
    }

    $limit = max(0, (int) $this->option('limit'));
    $apply = (bool) $this->option('apply');

    $query = DB::table('catalog_product_sources as cps')
        ->join('catalog_sources as cs', 'cs.id', '=', 'cps.source_id')
        ->join('products as p', 'p.id', '=', 'cps.product_id')
        ->where('cs.code', 'jlcpcb_parts_database')
        ->where('p.sku', 'like', 'JLCPCB-%')
        ->select('p.id as product_id', 'p.sku', 'cps.source_part_id');

    if ($limit > 0) {
        $query->limit($limit);
    }

    $rows = $query->orderBy('p.id')->get();
    $planned = [];
    $conflicts = [];

    foreach ($rows as $row) {
        $targetSku = 'NG-'.$row->source_part_id;
        $owner = DB::table('products')->where('sku', $targetSku)->where('id', '<>', $row->product_id)->first(['id', 'sku']);
        if ($owner) {
            $conflicts[] = [
                'product_id' => $row->product_id,
                'current_sku' => $row->sku,
                'target_sku' => $targetSku,
                'existing_product_id' => $owner->id,
            ];
            continue;
        }

        $planned[] = [
            'product_id' => (int) $row->product_id,
            'current_sku' => $row->sku,
            'target_sku' => $targetSku,
        ];
    }

    $this->line('Rows scanned: '.$rows->count());
    $this->line('Safe updates: '.count($planned));
    $this->line('Conflicts: '.count($conflicts));

    foreach (array_slice($planned, 0, 10) as $item) {
        $this->line("PLAN product #{$item['product_id']}: {$item['current_sku']} -> {$item['target_sku']}");
    }
    foreach (array_slice($conflicts, 0, 10) as $item) {
        $this->warn("CONFLICT product #{$item['product_id']}: {$item['target_sku']} already belongs to #{$item['existing_product_id']}");
    }

    if ($conflicts !== []) {
        $this->error('Refusing to apply while conflicts exist.');

        return self::FAILURE;
    }

    if (! $apply) {
        $this->comment('Dry-run only. Re-run with --apply to persist safe SKU repairs.');

        return self::SUCCESS;
    }

    DB::transaction(function () use ($planned) {
        foreach ($planned as $item) {
            DB::table('products')->where('id', $item['product_id'])->update([
                'sku' => $item['target_sku'],
                'updated_at' => now(),
            ]);

            if (Schema::hasTable('product_search_documents')) {
                DB::table('product_search_documents')->where('product_id', $item['product_id'])->update([
                    'sku' => $item['target_sku'],
                    'updated_at' => now(),
                ]);
            }
        }
    });

    $this->info('Updated '.count($planned).' JLCPCB-linked product SKU(s) to NG-*.');

    return self::SUCCESS;
})->purpose('Repair JLCPCB-linked product SKUs from JLCPCB-* to NeoGiga NG-* format.');

Schedule::job(new DetectAbandonedCartsJob)->everyFifteenMinutes();
Schedule::job(new CalculateTrendingProductsJob)->hourly();
Schedule::job(new CalculateTrendingCategoriesJob)->hourly();
Schedule::job(new CalculateTopSearchTermsJob)->hourly();
Schedule::job(new RefreshCustomerSegmentJob)->daily();
Schedule::job(new GenerateRegionalSalesReportJob)->daily();
