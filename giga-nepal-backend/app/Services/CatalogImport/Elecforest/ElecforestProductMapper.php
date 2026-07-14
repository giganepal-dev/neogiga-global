<?php

namespace App\Services\CatalogImport\Elecforest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ElecforestProductMapper
{
    public function __construct(
        private readonly ElecforestCategoryMapper $categories,
        private readonly ElecforestBrandResolver $brands,
        private readonly ElecforestManufacturerResolver $manufacturers,
        private readonly ElecforestSpecificationMapper $specifications,
        private readonly ElecforestContentRewriter $content,
    ) {}

    /** @param array<string, mixed> $record @return array<string, mixed> */
    public function map(array $record, int $sourceId, bool $persistCategories = true): array
    {
        $category = $this->categories->resolve((string) $record['main_category'], (string) $record['subcategory'], $sourceId, $persistCategories);
        $brand = $this->brands->resolve($record);
        $manufacturer = $this->manufacturers->resolve($record);
        $specifications = $this->specifications->map($record);
        $record['category_name'] = $category['category_name'];
        $content = $this->content->rewrite($record, $specifications);
        $hash = substr(hash('sha256', (string) $record['source_url']), 0, 10);
        $skuPart = $record['supplier_sku']
            ? trim(preg_replace('/[^A-Z0-9]+/', '-', strtoupper((string) $record['supplier_sku'])) ?? '', '-')
            : strtoupper($hash);
        $sku = Str::limit('NG-EF-'.$skuPart, 190, '');
        if (DB::table('products')->where('sku', $sku)->exists()) {
            $sku = Str::limit($sku, 178, '').'-'.strtoupper(substr($hash, 0, 8));
        }
        $slug = Str::limit(Str::slug((string) $content['name']).'-ef-'.$hash, 190, '');

        return compact('record', 'category', 'brand', 'manufacturer', 'specifications', 'content', 'sku', 'slug') + [
            'applications' => $this->specifications->applications($record),
        ];
    }
}
