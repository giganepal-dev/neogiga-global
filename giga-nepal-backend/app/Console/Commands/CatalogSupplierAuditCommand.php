<?php

namespace App\Console\Commands;

use App\Catalog\Ingestion\Validation\SupplierPolicyService;
use Illuminate\Console\Command;

class CatalogSupplierAuditCommand extends Command
{
    protected $signature = 'catalog:supplier-audit {supplier? : adafruit, waveshare, or okystar}';

    protected $description = 'Read supplier robots.txt and record a pending manual policy review; never enables imports.';

    public function handle(SupplierPolicyService $policy): int
    {
        $suppliers = $this->argument('supplier') ? [$this->argument('supplier')] : array_keys(config('catalog_import.suppliers'));
        foreach ($suppliers as $supplier) {
            try {
                $result = $policy->audit($supplier);
                $this->table(['Supplier', 'Robots', 'Policy', 'Sitemaps'], [[
                    $supplier, $result['robots_http_status'], $result['policy_status'], count($result['sitemaps']),
                ]]);
                $this->warn($result['reason']);
            } catch (\Throwable $exception) {
                $this->error("{$supplier}: {$exception->getMessage()}");

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
