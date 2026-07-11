<?php

namespace App\Jobs\Marketing;

use App\Services\Marketing\CustomerSegmentationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefreshCustomerSegmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload = [])
    {
    }

    public function handle(CustomerSegmentationService $segments): void
    {
        if (! Schema::hasTable('customer_segments') || ! Schema::hasTable('customer_segment_members')) {
            return;
        }

        $segmentId = (int) ($this->payload['segment_id'] ?? 0);
        $ids = $segmentId > 0
            ? collect([$segmentId])
            : DB::table('customer_segments')->where('is_active', true)->pluck('id');

        foreach ($ids as $id) {
            $segments->refresh((int) $id);
        }
    }
}
