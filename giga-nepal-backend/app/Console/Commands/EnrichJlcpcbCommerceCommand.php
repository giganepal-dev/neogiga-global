<?php

namespace App\Console\Commands;

use App\Services\Catalog\JlcpcbCommerceEnrichmentService;
use Illuminate\Console\Command;
use Throwable;

class EnrichJlcpcbCommerceCommand extends Command
{
    protected $signature = 'jlcpcb:enrich-commerce
        {--apply : Persist missing GLOBAL prices and supplier availability overlays; omitted means read-only dry run}
        {--yes : Explicitly confirm the verified apply plan}
        {--limit=0 : Maximum JLC source-linked products to scan, 0 means all}
        {--chunk= : Products per bounded database chunk (1-2000)}
        {--expected-plan-hash= : Exact SHA-256 plan hash printed by the latest matching dry run}
        {--backup-reference= : Verified database backup identifier required for apply}';

    protected $description = 'Dry-run or add JLC GLOBAL/USD prices and quote-only supplier availability without changing physical inventory';

    public function handle(JlcpcbCommerceEnrichmentService $enrichment): int
    {
        try {
            $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($limit === false) {
                throw new \RuntimeException('--limit must be a non-negative integer.');
            }
            $chunkOption = $this->option('chunk');
            $chunk = ($chunkOption === null || $chunkOption === '')
                ? null
                : filter_var($chunkOption, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2_000]]);
            if ($chunk === false) {
                throw new \RuntimeException('--chunk must be an integer from 1 to 2000.');
            }

            $plan = $enrichment->plan((int) $limit, $chunk);
            $this->line(json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            if (! (bool) $this->option('apply')) {
                $this->newLine();
                $this->info('Dry run only: no price, supplier availability, physical inventory, product, or UI row was changed.');
                $this->comment('After a verified backup, repeat with --apply, --expected-plan-hash and --backup-reference.');

                return self::SUCCESS;
            }
            if (! (bool) $this->option('yes')) {
                throw new \RuntimeException('--yes is required with --apply after reviewing the dry-run plan.');
            }

            $result = $enrichment->apply(
                (string) $this->option('expected-plan-hash'),
                (string) $this->option('backup-reference'),
                (int) $limit,
                $chunk,
            );
            $this->newLine();
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $this->info('JLC commerce enrichment completed without writing inventory_stocks.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
