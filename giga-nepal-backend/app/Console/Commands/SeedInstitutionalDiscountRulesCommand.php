<?php

namespace App\Console\Commands;

use Database\Seeders\InstitutionalDiscountRuleSeeder;
use Illuminate\Console\Command;

class SeedInstitutionalDiscountRulesCommand extends Command
{
    protected $signature = 'b2b:seed-institutional-discounts';

    protected $description = 'Seed pricing rules for government/school/corporate institutional discounts';

    public function handle(): int
    {
        (new InstitutionalDiscountRuleSeeder)->run();
        $this->info('Institutional discount pricing rules seeded from config/b2b_institutional.php');

        return self::SUCCESS;
    }
}
