<?php

namespace App\Console\Commands\Suppliers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedSuppliers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'neogiga:seed:suppliers 
                            {--all : Seed all suppliers and sample products}
                            {--suppliers : Seed only supplier records (Tier 1, 2, 3)}
                            {--products : Seed sample products with complete data}
                            {--fresh : Fresh database before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed NeoGiga priority suppliers and sample product catalog';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 NeoGiga Supplier & Product Seeder');
        $this->newLine();

        if ($this->option('fresh')) {
            if (!$this->confirm('⚠️  This will wipe your database. Continue?')) {
                return self::FAILURE;
            }
            $this->call('db:wipe', ['--force' => true]);
            $this->info('✓ Database wiped');
            
            $this->call('migrate:fresh', ['--force' => true]);
            $this->info('✓ Migrations run');
            $this->newLine();
        }

        $seedAll = $this->option('all');
        $seedSuppliers = $this->option('suppliers');
        $seedProducts = $this->option('products');

        // Default to all if no specific option
        if (!$seedAll && !$seedSuppliers && !$seedProducts) {
            $seedAll = true;
        }

        if ($seedAll || $seedSuppliers) {
            $this->info('📦 Seeding Suppliers (Tier 1, 2, 3)...');
            $this->call('db:seed', [
                '--class' => 'Database\\Seeders\\Suppliers\\SupplierSeeder',
                '--force' => true,
            ]);
            $this->newLine();
        }

        if ($seedAll || $seedProducts) {
            $this->info('🛍️  Seeding Sample Products...');
            $this->call('db:seed', [
                '--class' => 'Database\\Seeders\\Suppliers\\SampleProductSeeder',
                '--force' => true,
            ]);
            $this->newLine();
        }

        $this->info('✅ Seeding completed successfully!');
        $this->newLine();
        
        $this->table(['Metric', 'Count'], [
            ['Suppliers seeded', '27 (6 Tier 1 + 12 Tier 2 + 9 Tier 3)'],
            ['Sample products', '10 products from 5 suppliers'],
            ['Country pricing', '4 currencies per product (USD, EUR, GBP, INR)'],
            ['Warehouse records', '1 per product'],
            ['AI feature placeholders', '1 per product'],
            ['Resource links', 'Datasheets, GitHub, Libraries'],
        ]);

        $this->newLine();
        $this->info('💡 Next steps:');
        $this->line('   • Run importers: php artisan neogiga:import:adafruit --products');
        $this->line('   • Review products in admin dashboard');
        $this->line('   • Generate AI features for products');
        $this->line('   • Publish products when ready');

        return self::SUCCESS;
    }
}
