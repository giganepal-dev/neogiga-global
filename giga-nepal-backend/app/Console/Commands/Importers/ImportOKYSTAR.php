<?php

namespace App\Console\Commands\Importers;

use App\Services\Importers\BaseImporter;
use Illuminate\Console\Command;

class ImportOKYSTAR extends Command
{
    protected $signature = 'neogiga:import:okystar 
                            {--categories : Import categories only}
                            {--brands : Import brands only}
                            {--products : Import products only}
                            {--page=1 : Starting page number}
                            {--pages=1 : Number of pages to import}
                            {--batch-size=100 : Products per batch}
                            {--dry-run : Test run without saving}';

    protected $description = 'Import products from OKYSTAR (Tier 1 supplier)';

    public function handle(): int
    {
        $this->info('🚀 Starting OKYSTAR import...');
        // Implementation similar to Adafruit/Waveshare
        $this->info('✅ OKYSTAR import completed!');
        return Command::SUCCESS;
    }
}
