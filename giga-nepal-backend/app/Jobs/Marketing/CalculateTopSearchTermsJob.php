<?php

namespace App\Jobs\Marketing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CalculateTopSearchTermsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload = [])
    {
    }

    public function handle(): void
    {
        if (! Schema::hasTable('top_search_terms') || ! Schema::hasTable('product_searches')) {
            return;
        }

        $days = max(1, min(180, (int) ($this->payload['days'] ?? 30)));
        $limit = max(10, min(500, (int) ($this->payload['limit'] ?? 100)));
        $since = now()->subDays($days);
        $terms = [];

        DB::table('product_searches')
            ->select('query', DB::raw('count(*) as total'))
            ->whereNotNull('query')
            ->where('created_at', '>=', $since)
            ->groupBy('query')
            ->get()
            ->each(function ($row) use (&$terms) {
                $term = Str::of((string) $row->query)->squish()->lower()->limit(190, '')->toString();
                if ($term !== '') {
                    $terms[$term] = ($terms[$term] ?? 0) + (int) $row->total;
                }
            });

        arsort($terms);
        $rows = [];
        foreach (array_slice($terms, 0, $limit, true) as $term => $count) {
            $rows[] = [
                'term' => $term,
                'search_count' => $count,
                'metadata' => json_encode(['window_days' => $days, 'source' => 'scheduled_marketing_job']),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($rows) {
            DB::table('top_search_terms')->delete();
            if ($rows !== []) {
                DB::table('top_search_terms')->insert($rows);
            }
        });
    }
}
