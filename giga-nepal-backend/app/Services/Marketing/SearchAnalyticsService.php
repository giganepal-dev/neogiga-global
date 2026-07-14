<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class SearchAnalyticsService
{
    public function count(array $filters = []): int
    {
        return DB::table('customer_profiles')->count();
    }

    public function preview(array $filters = [], int $limit = 25): array
    {
        return DB::table('customer_profiles')->limit($limit)->get()->toArray();
    }
}
