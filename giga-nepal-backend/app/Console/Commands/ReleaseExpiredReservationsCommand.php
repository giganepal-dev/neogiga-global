<?php

namespace App\Console\Commands;

use App\Services\Inventory\InventoryReservationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredReservationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:release-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release expired cart inventory reservations (15-minute TTL) and restore stock to inventory';

    protected InventoryReservationService $reservationService;

    /**
     * Execute the console command.
     */
    public function handle(InventoryReservationService $reservationService): int
    {
        $this->reservationService = $reservationService;

        $this->info('Starting expired reservation cleanup...');

        try {
            $result = $this->reservationService->releaseExpiredReservations();

            $this->info("Released {$result['released_count']} expired reservations");
            $this->info("Restored {$result['restored_quantity']} units to inventory");

            Log::channel('daily')->info('Expired reservations cleanup completed', [
                'released_count' => $result['released_count'],
                'restored_quantity' => $result['restored_quantity'],
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to release expired reservations: ' . $e->getMessage());
            
            Log::channel('daily')->error('Expired reservations cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
