<?php

namespace App\Console\Commands;

use App\Services\Catalog\JlcpcbQualifiedPublicationService;
use Illuminate\Console\Command;
use Throwable;

class PublishQualifiedJlcpcbProductsCommand extends Command
{
    protected $signature = 'catalog:jlcpcb-publish-qualified
        {--apply : Apply the current governed plan; omitted means read-only dry run}
        {--yes : Required explicit confirmation for --apply}
        {--plan-hash= : Exact SHA-256 plan hash printed by the current dry run}
        {--backup-dir= : Child of JLCPCB_BACKUP_ROOT with verified manifest, checksums, and restore verification}
        {--batch-size= : Products per bounded transaction}
        {--min-quality= : Override the configured minimum data-quality score (0-1)}';

    protected $description = 'Dry-run and optionally approve only fully qualified JLCPCB imports without changing storefront design or external media state';

    public function handle(JlcpcbQualifiedPublicationService $publication): int
    {
        try {
            $minimumQuality = $this->qualityOption();
            $plan = $publication->plan($minimumQuality);
            $this->line(json_encode(
                $publication->forOutput($plan),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ));

            if (! (bool) $this->option('apply')) {
                $this->newLine();
                $this->info('Dry run only: no products, source reviews, offers, images, datasheets, prices, or UI state were changed.');

                return self::SUCCESS;
            }

            if (! (bool) $this->option('yes')) {
                throw new \RuntimeException('--apply requires --yes.');
            }

            $planHash = strtolower(trim((string) $this->option('plan-hash')));
            if (preg_match('/^[a-f0-9]{64}$/', $planHash) !== 1) {
                throw new \RuntimeException('--apply requires the exact 64-character --plan-hash from the current dry run.');
            }
            $backupDirectory = trim((string) $this->option('backup-dir'));
            if ($backupDirectory === '') {
                throw new \RuntimeException('--apply requires --backup-dir with verified MANIFEST.txt, SHA256SUMS, and restore-verification files.');
            }

            $result = $publication->apply(
                $planHash,
                $backupDirectory,
                $minimumQuality,
                $this->batchSizeOption(),
            );
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

    private function qualityOption(): ?float
    {
        $value = $this->option('min-quality');
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            throw new \RuntimeException('--min-quality must be a number between 0 and 1.');
        }
        $quality = (float) $value;
        if (! is_finite($quality) || $quality < 0 || $quality > 1) {
            throw new \RuntimeException('--min-quality must be a number between 0 and 1.');
        }

        return $quality;
    }

    private function batchSizeOption(): ?int
    {
        $value = $this->option('batch-size');
        if ($value === null || $value === '') {
            return null;
        }
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new \RuntimeException('--batch-size must be an integer within the configured bounded range.');
        }

        return (int) $value;
    }
}
