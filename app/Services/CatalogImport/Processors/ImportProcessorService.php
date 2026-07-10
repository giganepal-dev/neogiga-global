<?php

namespace NeoGiga\CatalogImport\Services\Processors;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use NeoGiga\CatalogImport\Models\ImportRow;
use NeoGiga\CatalogImport\Models\ImportBatch;
use NeoGiga\CatalogImport\Models\StagedManufacturer;
use NeoGiga\CatalogImport\Models\StagedProduct;
use NeoGiga\CatalogImport\Models\CatalogSource;
use NeoGiga\CatalogImport\Services\Mapping\FieldMapperService;
use NeoGiga\CatalogImport\Services\Validators\DataQualityValidator;

class ImportProcessorService
{
    protected FieldMapperService $fieldMapper;
    protected DataQualityValidator $qualityValidator;

    public function __construct(
        FieldMapperService $fieldMapper,
        DataQualityValidator $qualityValidator
    ) {
        $this->fieldMapper = $fieldMapper;
        $this->qualityValidator = $qualityValidator;
    }

    /**
     * Process a single import row through the ETL pipeline
     */
    public function processRow(ImportRow $row, ImportBatch $batch): void
    {
        $payload = json_decode($row->payload, true);
        $mappedData = $payload['mapped_data'] ?? [];

        // Step 1: Apply source-specific field mappings
        $mappedData = $this->fieldMapper->applySourceMapping(
            $row->catalog_source_id,
            $mappedData
        );

        // Step 2: Resolve manufacturer
        $manufacturerResult = $this->resolveManufacturer($mappedData, $row);
        
        // Step 3: Resolve category
        $categoryResult = $this->resolveCategory($mappedData, $row);

        // Step 4: Normalize attributes and units
        $attributes = $this->normalizeAttributes($mappedData['attributes'] ?? []);

        // Step 5: Calculate data quality score
        $qualityScore = $this->qualityValidator->calculateScore([
            'manufacturer_matched' => $manufacturerResult['matched'],
            'category_matched' => $categoryResult['matched'],
            'mpn_present' => !empty($mappedData['manufacturer_part_number']),
            'name_present' => !empty($mappedData['product_name']),
            'datasheet_present' => !empty($mappedData['datasheet_url']),
            'attributes_count' => count($attributes)
        ]);

        // Step 6: Determine if review is needed
        $needsReview = $this->determineReviewRequirement(
            $manufacturerResult,
            $categoryResult,
            $qualityScore,
            $mappedData
        );

        // Step 7: Stage the product for review/publishing
        $this->stageProduct($row, $batch, $mappedData, [
            'manufacturer_id' => $manufacturerResult['id'] ?? null,
            'manufacturer_match_confidence' => $manufacturerResult['confidence'] ?? 0,
            'category_id' => $categoryResult['id'] ?? null,
            'category_match_confidence' => $categoryResult['confidence'] ?? 0,
            'normalized_attributes' => $attributes,
            'quality_score' => $qualityScore,
            'needs_review' => $needsReview,
            'review_reasons' => $needsReview ? $this->getReviewReasons($manufacturerResult, $categoryResult, $qualityScore) : []
        ]);
    }

    /**
     * Resolve manufacturer from import data
     */
    protected function resolveManufacturer(array $data, ImportRow $row): array
    {
        $name = $data['manufacturer_name'] ?? $data['manufacturer'] ?? null;
        $externalId = $data['manufacturer_external_id'] ?? null;
        $sourceId = $row->catalog_source_id;

        if (!$name) {
            return ['matched' => false, 'reason' => 'missing_name'];
        }

        $manufacturer = $this->fieldMapper->resolveManufacturer($name, $externalId, $sourceId);

        if ($manufacturer) {
            return [
                'matched' => true,
                'id' => $manufacturer->id,
                'confidence' => 100,
                'manufacturer' => $manufacturer
            ];
        }

        // No match found - stage for review
        return [
            'matched' => false,
            'reason' => 'unknown_manufacturer',
            'proposed_name' => $name
        ];
    }

    /**
     * Resolve category from import data
     */
    protected function resolveCategory(array $data, ImportRow $row): array
    {
        $categoryPath = $data['category_path'] ?? null;
        $categoryName = $data['category_name'] ?? $data['category'] ?? null;

        if ($categoryPath) {
            $category = $this->fieldMapper->resolveCategory($categoryPath);
            if ($category) {
                return [
                    'matched' => true,
                    'id' => $category->id,
                    'confidence' => 100,
                    'category' => $category
                ];
            }
        }

        if ($categoryName) {
            // Try single name match
            $category = $this->fieldMapper->resolveCategory([$categoryName]);
            if ($category) {
                return [
                    'matched' => true,
                    'id' => $category->id,
                    'confidence' => 80,
                    'category' => $category
                ];
            }
        }

        return [
            'matched' => false,
            'reason' => 'unknown_category',
            'proposed_name' => $categoryName ?? implode(' > ', $categoryPath ?? [])
        ];
    }

    /**
     * Normalize attribute names and convert units
     */
    protected function normalizeAttributes(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $attr) {
            $name = $attr['name'] ?? null;
            $value = $attr['value'] ?? null;
            $unit = $attr['unit'] ?? null;

            if (!$name || $value === null) {
                continue;
            }

            // Resolve attribute definition
            $attributeDef = $this->fieldMapper->resolveAttribute($name);

            if ($attributeDef) {
                // Convert unit if needed
                $convertedValue = $this->convertUnit($value, $unit, $attributeDef->default_unit);
                
                $normalized[] = [
                    'attribute_id' => $attributeDef->id,
                    'attribute_code' => $attributeDef->code,
                    'original_value' => $value,
                    'original_unit' => $unit,
                    'normalized_value' => $convertedValue,
                    'normalized_unit' => $attributeDef->default_unit
                ];
            } else {
                // Unknown attribute - preserve for review
                $normalized[] = [
                    'attribute_code' => null,
                    'original_name' => $name,
                    'original_value' => $value,
                    'original_unit' => $unit,
                    'needs_mapping' => true
                ];
            }
        }

        return $normalized;
    }

    /**
     * Convert value between units
     */
    protected function convertUnit($value, ?string $fromUnit, ?string $toUnit)
    {
        if (!$fromUnit || !$toUnit || $fromUnit === $toUnit) {
            return $value;
        }

        // Simple conversion logic - extend with full conversion table
        $conversions = [
            'V' => ['mV' => 0.001, 'kV' => 1000],
            'A' => ['mA' => 0.001, 'uA' => 0.000001],
            'Ω' => ['kΩ' => 1000, 'MΩ' => 1000000],
            '°C' => ['°F' => fn($v) => ($v - 32) * 5/9],
        ];

        // Implement actual conversion logic based on unit family
        // For now, return original value if no conversion found
        return $value;
    }

    /**
     * Determine if row needs manual review
     */
    protected function determineReviewRequirement(
        array $manufacturerResult,
        array $categoryResult,
        int $qualityScore,
        array $data
    ): bool {
        // Auto-review triggers
        if (!$manufacturerResult['matched']) {
            return true;
        }

        if (!$categoryResult['matched']) {
            return true;
        }

        if ($qualityScore < 70) {
            return true;
        }

        // Check for duplicate MPN
        if (!empty($data['manufacturer_part_number']) && $manufacturerResult['id']) {
            $exists = StagedProduct::where('manufacturer_id', $manufacturerResult['id'])
                ->where('manufacturer_part_number', $data['manufacturer_part_number'])
                ->exists();
            
            if ($exists) {
                return true; // Duplicate needs review
            }
        }

        return false;
    }

    /**
     * Get human-readable review reasons
     */
    protected function getReviewReasons(array $manufacturerResult, array $categoryResult, int $qualityScore): array
    {
        $reasons = [];

        if (!$manufacturerResult['matched']) {
            $reasons[] = 'Unknown manufacturer: ' . ($manufacturerResult['proposed_name'] ?? '');
        }

        if (!$categoryResult['matched']) {
            $reasons[] = 'Unknown category: ' . ($categoryResult['proposed_name'] ?? '');
        }

        if ($qualityScore < 40) {
            $reasons[] = 'Low data quality score (' . $qualityScore . ')';
        } elseif ($qualityScore < 70) {
            $reasons[] = 'Incomplete product data (' . $qualityScore . ')';
        }

        return $reasons;
    }

    /**
     * Stage product record for review/approval
     */
    protected function stageProduct(
        ImportRow $row,
        ImportBatch $batch,
        array $data,
        array $results
    ): void {
        StagedProduct::create([
            'import_row_id' => $row->id,
            'import_batch_id' => $batch->id,
            'catalog_source_id' => $row->catalog_source_id,
            'manufacturer_id' => $results['manufacturer_id'],
            'manufacturer_name_proposed' => $data['manufacturer_name'] ?? $data['manufacturer'] ?? null,
            'manufacturer_match_confidence' => $results['manufacturer_match_confidence'] ?? 0,
            'category_id' => $results['category_id'],
            'category_name_proposed' => $data['category_name'] ?? $data['category'] ?? null,
            'category_match_confidence' => $results['category_match_confidence'] ?? 0,
            'manufacturer_part_number' => $data['manufacturer_part_number'] ?? null,
            'product_name' => $data['product_name'] ?? null,
            'description' => $data['description'] ?? null,
            'datasheet_url' => $data['datasheet_url'] ?? null,
            'image_urls' => json_encode($data['image_urls'] ?? []),
            'attributes' => json_encode($results['normalized_attributes']),
            'quality_score' => $results['quality_score'],
            'status' => $results['needs_review'] ? 'pending_review' : 'ready_for_import',
            'review_reasons' => json_encode($results['review_reasons']),
            'raw_payload' => $row->payload,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
