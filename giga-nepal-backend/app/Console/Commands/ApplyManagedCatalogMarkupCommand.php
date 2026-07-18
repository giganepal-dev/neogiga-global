<?php

namespace App\Console\Commands;

use App\Services\Pricing\ManagedCatalogMarkupService;
use Illuminate\Console\Command;
use Throwable;

class ApplyManagedCatalogMarkupCommand extends Command
{
    protected $signature = 'pricing:managed-catalog-markup
        {--markup=20 : Markup percent applied to the stored cost basis}
        {--chunk=500 : Products per bounded transaction (1-2000)}
        {--apply : Persist the current dry-run plan}
        {--yes : Explicitly authorize the apply operation}
        {--expected-plan-hash= : Exact SHA-256 plan hash from the dry run}
        {--backup-reference= : Verified backup directory required for apply}';

    protected $description = 'Dry-run or apply a markup to source-managed catalog prices without overwriting manual or seller prices';

    public function handle(ManagedCatalogMarkupService $pricing): int
    {
        try {
            $markup = filter_var($this->option('markup'), FILTER_VALIDATE_FLOAT);
            $chunk = filter_var($this->option('chunk'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2000]]);
            if ($markup === false || $chunk === false) {
                throw new \RuntimeException('--markup must be numeric and --chunk must be an integer from 1 to 2000.');
            }

            $plan = $pricing->plan((float) $markup, (int) $chunk);
            $this->line(json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            if (! $this->option('apply')) {
                $this->newLine();
                $this->info('Dry run only: no price, history, product, supplier, or inventory row was changed.');

                return self::SUCCESS;
            }
            if (! $this->option('yes')) {
                throw new \RuntimeException('--yes is required with --apply.');
            }

            $result = $pricing->apply(
                (float) $markup,
                (string) $this->option('expected-plan-hash'),
                (string) $this->option('backup-reference'),
                (int) $chunk,
            );
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
