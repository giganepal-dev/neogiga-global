<?php

namespace App\Catalog\Ingestion\Reports;

use Illuminate\Support\Facades\Storage;

class CatalogImportReporter
{
    /** @param array<string, mixed> $report */
    public function write(string $runId, array $report): string
    {
        $directory = "catalog/reports/{$runId}";
        Storage::disk('local')->put("{$directory}/summary.json", json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        Storage::disk('local')->put("{$directory}/products.csv", "source_product_id,status,reason\n");
        Storage::disk('local')->put("{$directory}/errors.csv", "source_product_id,reason\n");
        Storage::disk('local')->put("{$directory}/missing-fields.csv", "source_product_id,field\n");
        Storage::disk('local')->put("{$directory}/duplicates.csv", "source_product_id,reason\n");
        Storage::disk('local')->put("{$directory}/category-mappings.csv", "source_category,mapped_category,confidence\n");
        Storage::disk('local')->put("{$directory}/media-failures.csv", "source_product_id,asset_url,reason\n");
        Storage::disk('local')->put("{$directory}/changes.csv", "source_product_id,event\n");

        return storage_path("app/{$directory}");
    }
}
