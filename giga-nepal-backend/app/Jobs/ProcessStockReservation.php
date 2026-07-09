<?php

namespace App\Jobs;

use App\Services\Inventory\InventoryReservationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessStockReservation implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     * 
     * Release expired cart reservations (15-minute TTL)
     * This job should run every minute via Laravel scheduler
     */
    public function handle(InventoryReservationService $reservationService): void
    {
        Log::info('Starting expired reservation cleanup job');

        try {
            $result = $reservationService->releaseExpiredReservations();

            Log::info('Expired reservation cleanup completed', [
                'released_count' => $result['released_count'],
                'restored_quantity' => $result['restored_quantity'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process expired reservations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
