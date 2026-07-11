<?php

namespace App\Jobs\Marketing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GenerateRegionalSalesReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload = [])
    {
    }

    public function handle(): void
    {
        if (! Schema::hasTable('regional_sales_reports') || ! Schema::hasTable('orders')) {
            return;
        }

        $days = max(1, min(365, (int) ($this->payload['days'] ?? 1)));
        $start = now()->subDays($days)->startOfDay();
        $end = now();
        $query = DB::table('orders as o')
            ->leftJoin('marketplaces as m', 'm.id', '=', 'o.marketplace_id')
            ->select(
                'm.country_id',
                DB::raw('null::bigint as region_id'),
                DB::raw('count(o.id) as order_count'),
                DB::raw('sum(o.grand_total) as amount')
            )
            ->where('o.created_at', '>=', $start)
            ->where('o.created_at', '<=', $end)
            ->whereNotIn('o.status', ['cancelled', 'failed'])
            ->groupBy('m.country_id');

        $rows = $query->get()->map(fn ($row) => [
            'country_id' => $row->country_id,
            'region_id' => null,
            'amount' => $row->amount ?? 0,
            'metadata' => json_encode([
                'order_count' => (int) $row->order_count,
                'window_days' => $days,
                'source' => 'scheduled_marketing_job',
            ]),
            'occurred_at' => $end,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        DB::transaction(function () use ($start, $rows) {
            DB::table('regional_sales_reports')->where('occurred_at', '>=', $start)->delete();
            if ($rows !== []) {
                DB::table('regional_sales_reports')->insert($rows);
            }
        });
    }
}
