<?php

namespace App\Jobs\Marketing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CalculateTrendingCategoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload = []) {}

    public function handle(): void
    {
        if (! Schema::hasTable('trending_categories')) {
            return;
        }

        $days = max(1, min(180, (int) ($this->payload['days'] ?? 30)));
        $limit = max(10, min(500, (int) ($this->payload['limit'] ?? 100)));
        $since = now()->subDays($days);
        $scores = [];

        if (Schema::hasTable('category_views')) {
            DB::table('category_views')
                ->select('category_id', DB::raw('count(*) as total'))
                ->whereNotNull('category_id')
                ->where('created_at', '>=', $since)
                ->groupBy('category_id')
                ->get()
                ->each(function ($row) use (&$scores) {
                    $scores[(int) $row->category_id] = ($scores[(int) $row->category_id] ?? 0) + ((int) $row->total * 2);
                });
        }

        if (Schema::hasTable('product_views') && Schema::hasTable('products')) {
            DB::table('product_views as pv')
                ->join('products as p', 'p.id', '=', 'pv.product_id')
                ->select('p.category_id', DB::raw('count(*) as total'))
                ->whereNotNull('p.category_id')
                ->where('pv.created_at', '>=', $since)
                ->groupBy('p.category_id')
                ->get()
                ->each(function ($row) use (&$scores) {
                    $scores[(int) $row->category_id] = ($scores[(int) $row->category_id] ?? 0) + (int) $row->total;
                });
        }

        arsort($scores);
        $rows = [];
        foreach (array_slice($scores, 0, $limit, true) as $categoryId => $score) {
            $rows[] = [
                'category_id' => $categoryId,
                'score' => $score,
                'metadata' => json_encode(['window_days' => $days, 'source' => 'scheduled_marketing_job']),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($rows) {
            DB::table('trending_categories')->delete();
            if ($rows !== []) {
                DB::table('trending_categories')->insert($rows);
            }
        });
    }
}
