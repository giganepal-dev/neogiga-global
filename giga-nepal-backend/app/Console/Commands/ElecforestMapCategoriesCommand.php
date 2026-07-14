<?php

namespace App\Console\Commands;

use App\Services\CatalogImport\Elecforest\ElecforestImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ElecforestMapCategoriesCommand extends Command
{
    protected $signature = 'catalog:elecforest-map-categories
        {--source-category= : Main or Main / Subcategory}
        {--neo-category= : Existing NeoGiga category name or slug}
        {--pending : List only pending mappings}';
    protected $description = 'Review or approve ElecForest-to-NeoGiga category mappings';

    public function handle(ElecforestImporter $importer): int
    {
        $source = trim((string) $this->option('source-category'));
        $neo = trim((string) $this->option('neo-category'));
        try {
            if ($source !== '' && $neo !== '') {
                $parts = preg_split('/\s*(?:\/|>|\|)\s*/', $source, 2) ?: [];
                $result = $importer->mapCategory(trim($parts[0] ?? ''), trim($parts[1] ?? ''), $neo);
                $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                return self::SUCCESS;
            }
            $query = DB::table('supplier_category_mappings as m')->join('catalog_sources as s', 's.id', '=', 'm.catalog_source_id')
                ->leftJoin('product_categories as c', 'c.id', '=', 'm.category_id')->where('s.code', 'elecforest')
                ->select(['m.source_category_path', 'c.name as neo_category', 'm.confidence', 'm.mapping_status'])->orderBy('m.source_category_path');
            if ($this->option('pending')) {
                $query->where('m.mapping_status', 'pending_review');
            }
            $this->table(['Source category', 'NeoGiga category', 'Confidence', 'Status'], $query->get()->map(fn ($row) => (array) $row));
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
