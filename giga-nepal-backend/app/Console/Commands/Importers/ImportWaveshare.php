<?php

namespace App\Console\Commands\Importers;

use App\Services\Importers\WaveshareImporter;
use Illuminate\Console\Command;

class ImportWaveshare extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'neogiga:import:waveshare 
                            {--categories : Import categories only}
                            {--brands : Import brands only}
                            {--products : Import products only}
                            {--page=1 : Starting page number}
                            {--pages=1 : Number of pages to import}
                            {--batch-size=100 : Products per batch}
                            {--dry-run : Test run without saving}';

    /**
     * The console command description.
     */
    protected $description = 'Import products from Waveshare (Tier 1 supplier)';

    protected WaveshareImporter $importer;

    public function __construct(WaveshareImporter $importer)
    {
        parent::__construct();
        $this->importer = $importer;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting Waveshare import...');
        $this->importer->setCommand($this);

        if ($this->option('dry-run')) {
            $this->warn('⚠️  DRY RUN MODE - No data will be saved');
        }

        try {
            // Import categories
            if ($this->option('categories') || !$this->option('products') && !$this->option('brands')) {
                $this->importCategories();
            }

            // Import brands
            if ($this->option('brands') || !$this->option('products') && !$this->option('categories')) {
                $this->importBrands();
            }

            // Import products
            if ($this->option('products') || !$this->option('categories') && !$this->option('brands')) {
                $this->importProducts();
            }

            $this->info('✅ Waveshare import completed successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Import failed: ' . $e->getMessage());
            $this->debug($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Import categories from Waveshare
     */
    protected function importCategories(): void
    {
        $this->info('📁 Importing categories...');
        
        $categories = $this->importer->fetchCategories();
        $count = count($categories);
        
        if ($this->option('dry-run')) {
            $this->info("Would import {$count} categories");
            return;
        }

        $imported = $this->importer->importCategories($categories);
        $this->info("✅ Imported {$imported} categories");
    }

    /**
     * Import brands from Waveshare
     */
    protected function importBrands(): void
    {
        $this->info('🏷️  Importing brands...');
        
        $brands = $this->importer->fetchBrands();
        $count = count($brands);
        
        if ($this->option('dry-run')) {
            $this->info("Would import {$count} brands");
            return;
        }

        $imported = $this->importer->importBrands($brands);
        $this->info("✅ Imported {$imported} brands");
    }

    /**
     * Import products from Waveshare
     */
    protected function importProducts(): void
    {
        $page = (int) $this->option('page');
        $pages = (int) $this->option('pages');
        $batchSize = (int) $this->option('batch-size');

        $this->info("📦 Importing products (Page {$page}-" . ($page + $pages - 1) . ", Batch: {$batchSize})...");

        $totalImported = 0;
        $totalSkipped = 0;

        for ($i = 0; $i < $pages; $i++) {
            $currentPage = $page + $i;
            $this->line("Processing page {$currentPage}...");

            $products = $this->importer->fetchProducts($currentPage, $batchSize);
            
            if (empty($products)) {
                $this->warn("No products found on page {$currentPage}");
                break;
            }

            if ($this->option('dry-run')) {
                $totalImported += count($products);
                continue;
            }

            $result = $this->importer->importProducts($products);
            $totalImported += $result['imported'];
            $totalSkipped += $result['skipped'];

            $this->line("  → Imported: {$result['imported']}, Skipped: {$result['skipped']}");
        }

        $this->info("✅ Total: {$totalImported} imported, {$totalSkipped} skipped");
    }
}
