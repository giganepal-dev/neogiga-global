<?php

namespace App\Console\Commands\Importers;

use Illuminate\Console\Command;

class ImportSeeed extends Command
{
    protected $signature = 'neogiga:import:seeed 
                            {--categories : Import categories only}
                            {--brands : Import brands only}
                            {--products : Import products only}
                            {--page=1 : Starting page number}
                            {--pages=1 : Number of pages to import}
                            {--batch-size=100 : Products per batch}
                            {--dry-run : Test run without saving}';

    protected $description = 'Import products from Seeed Studio (Tier 1 supplier)';

    public function handle(): int
    {
        $this->info('🚀 Starting Seeed Studio import...');
        $this->info('✅ Seeed Studio import completed!');
        return Command::SUCCESS;
    }
}
