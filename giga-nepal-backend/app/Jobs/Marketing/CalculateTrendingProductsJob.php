<?php

namespace App\Jobs\Marketing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CalculateTrendingProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload = []) {}

    public function handle(): void
    {
        if (! Schema::hasTable('trending_products')) {
            return;
        }

        $days = max(1, min(180, (int) ($this->payload['days'] ?? 30)));
        $limit = max(10, min(500, (int) ($this->payload['limit'] ?? 100)));
        $since = now()->subDays($days);
        $scores = [];

        if (Schema::hasTable('product_views')) {
            DB::table('product_views')
                ->select('product_id', DB::raw('count(*) as total'))
                ->whereNotNull('product_id')
                ->where('created_at', '>=', $since)
                ->groupBy('product_id')
                ->get()
                ->each(function ($row) use (&$scores) {
                    $scores[(int) $row->product_id] = ($scores[(int) $row->product_id] ?? 0) + ((int) $row->total * 1);
                });
        }

        if (Schema::hasTable('add_to_cart_events')) {
            DB::table('add_to_cart_events')
                ->select('product_id', DB::raw('count(*) as total'))
                ->whereNotNull('product_id')
                ->where('created_at', '>=', $since)
                ->groupBy('product_id')
                ->get()
                ->each(function ($row) use (&$scores) {
                    $scores[(int) $row->product_id] = ($scores[(int) $row->product_id] ?? 0) + ((int) $row->total * 3);
                });
        }

        if (Schema::hasTable('order_items')) {
            DB::table('order_items')
                ->select('product_id', DB::raw('sum(quantity) as total'))
                ->whereNotNull('product_id')
                ->where('created_at', '>=', $since)
                ->groupBy('product_id')
                ->get()
                ->each(function ($row) use (&$scores) {
                    $scores[(int) $row->product_id] = ($scores[(int) $row->product_id] ?? 0) + ((int) $row->total * 5);
                });
        }

        arsort($scores);
        $rows = [];
        foreach (array_slice($scores, 0, $limit, true) as $productId => $score) {
            $rows[] = [
                'product_id' => $productId,
                'score' => $score,
                'metadata' => json_encode(['window_days' => $days, 'source' => 'scheduled_marketing_job']),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($rows) {
            DB::table('trending_products')->delete();
            if ($rows !== []) {
                DB::table('trending_products')->insert($rows);
            }
        });
    }
}
