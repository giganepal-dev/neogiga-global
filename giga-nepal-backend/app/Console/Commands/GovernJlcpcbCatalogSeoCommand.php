<?php

namespace App\Console\Commands;

use App\Services\Seo\JlcpcbCatalogSeoGovernanceService;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class GovernJlcpcbCatalogSeoCommand extends Command
{
    protected $signature = 'catalog:jlcpcb-govern-seo
        {--apply : Apply the current governed plan; omitted means read-only dry run}
        {--yes : Required explicit confirmation for --apply}
        {--plan-hash= : Exact SHA-256 plan hash printed by the current dry run}
        {--backup-dir= : Child of JLCPCB_BACKUP_ROOT with verified manifest, checksums, and restore verification}
        {--batch-size=250 : Rows per bounded transaction (1-1000)}';

    protected $description = 'Insert only missing JLCPCB product SEO and safely transition untouched importer-owned brand/category SEO metadata';

    public function handle(JlcpcbCatalogSeoGovernanceService $governance): int
    {
        try {
            $plan = $governance->plan();
            $this->line(json_encode(
                $governance->forOutput($plan),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ));

            if (! (bool) $this->option('apply')) {
                $this->newLine();
                $this->info('Dry run only: no product SEO, brand SEO, category SEO, marketplace, route, or frontend state was changed.');

                return self::SUCCESS;
            }
            if (! (bool) $this->option('yes')) {
                throw new RuntimeException('--apply requires --yes.');
            }

            $planHash = strtolower(trim((string) $this->option('plan-hash')));
            if (preg_match('/^[a-f0-9]{64}$/', $planHash) !== 1) {
                throw new RuntimeException('--apply requires the exact 64-character --plan-hash from the current dry run.');
            }
            $backupDirectory = trim((string) $this->option('backup-dir'));
            if ($backupDirectory === '') {
                throw new RuntimeException('--apply requires --backup-dir with verified manifest, checksums, and restore-verification files.');
            }

            $result = $governance->apply($planHash, $backupDirectory, $this->batchSize());
            $this->newLine();
            $this->line(json_encode(
                $result,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function batchSize(): int
    {
        $value = $this->option('batch-size');
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new RuntimeException('--batch-size must be an integer between 1 and 1000.');
        }
        $size = (int) $value;
        if ($size < 1 || $size > 1_000) {
            throw new RuntimeException('--batch-size must be an integer between 1 and 1000.');
        }

        return $size;
    }
}
