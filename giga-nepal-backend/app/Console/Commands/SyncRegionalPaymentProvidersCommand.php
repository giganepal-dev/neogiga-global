<?php

namespace App\Console\Commands;

use App\Services\Payments\RegionalPaymentProviderSyncService;
use Illuminate\Console\Command;

class SyncRegionalPaymentProvidersCommand extends Command
{
    protected $signature = 'payments:sync-regional {--dry-run : Report without writing}';

    protected $description = 'Seed payment_providers from config/neogiga_global.php per marketplace';

    public function handle(RegionalPaymentProviderSyncService $sync): int
    {
        if ($this->option('dry-run')) {
            $this->info('Dry run — would sync from config/neogiga_global.payment_gateways');

            return self::SUCCESS;
        }

        $stats = $sync->sync();
        $this->info(sprintf(
            'Regional payment providers synced: %d created, %d updated, %d skipped (no marketplace).',
            $stats['created'],
            $stats['updated'],
            $stats['skipped'],
        ));

        return self::SUCCESS;
    }
}
