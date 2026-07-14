<?php

namespace App\Services\CatalogImport\Elecforest;

use Illuminate\Support\Facades\DB;

class ElecforestIdentityResolver
{
    /** @param array<string, mixed> $record @param array<string, mixed> $manufacturer @return array{product_id:?int,matched_by:?string,ambiguous:bool} */
    public function resolve(array $record, array $manufacturer, int $sourceId): array
    {
        $sourceRecord = DB::table('supplier_products')
            ->where('catalog_source_id', $sourceId)
            ->where('source_product_id', $record['source_product_id'])
            ->first();
        if ($sourceRecord?->product_id) {
            return ['product_id' => (int) $sourceRecord->product_id, 'matched_by' => 'source_product_id', 'ambiguous' => false];
        }

        $mpn = $this->normalize($manufacturer['mpn'] ?? null);
        if ($mpn !== '' && ! empty($manufacturer['id'])) {
            $ids = DB::table('products')->where('manufacturer_id', $manufacturer['id'])->where('normalized_mpn', $mpn)->limit(2)->pluck('id');
            if ($ids->count() === 1) {
                return ['product_id' => (int) $ids->first(), 'matched_by' => 'manufacturer_mpn', 'ambiguous' => false];
            }
            if ($ids->count() > 1) {
                return ['product_id' => null, 'matched_by' => null, 'ambiguous' => true];
            }
        }

        if (! empty($record['supplier_sku'])) {
            $ids = DB::table('supplier_products')
                ->where('catalog_source_id', $sourceId)
                ->whereRaw('upper(trim(supplier_sku)) = ?', [strtoupper(trim((string) $record['supplier_sku']))])
                ->whereNotNull('product_id')->limit(2)->pluck('product_id')->unique();
            if ($ids->count() === 1) {
                return ['product_id' => (int) $ids->first(), 'matched_by' => 'supplier_sku', 'ambiguous' => false];
            }
            if ($ids->count() > 1) {
                return ['product_id' => null, 'matched_by' => null, 'ambiguous' => true];
            }
        }

        if ($record['source_url'] !== '') {
            $sourceUrlProduct = DB::table('supplier_products')
                ->where('catalog_source_id', $sourceId)->where('source_url', $record['source_url'])->value('product_id');
            if ($sourceUrlProduct) {
                return ['product_id' => (int) $sourceUrlProduct, 'matched_by' => 'source_url', 'ambiguous' => false];
            }
        }

        foreach (['gtin', 'ean', 'upc', 'barcode'] as $type) {
            $value = $this->normalize($record[$type] ?? null);
            if ($value === '') {
                continue;
            }
            $ids = DB::table('product_identifiers')->where('identifier_type', $type)->where('normalized_value', $value)
                ->where('is_verified', true)->limit(2)->pluck('product_id')->unique();
            if ($ids->count() === 1) {
                return ['product_id' => (int) $ids->first(), 'matched_by' => $type, 'ambiguous' => false];
            }
            if ($ids->count() > 1) {
                return ['product_id' => null, 'matched_by' => null, 'ambiguous' => true];
            }
        }

        return ['product_id' => null, 'matched_by' => null, 'ambiguous' => false];
    }

    public function normalize(mixed $value): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]+/i', '', trim((string) $value)) ?? '');
    }
}
