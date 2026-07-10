<?php

namespace NeoGiga\CatalogImport\Services\Parsers;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use NeoGiga\CatalogImport\Models\ImportFile;
use NeoGiga\CatalogImport\Models\ImportRow;
use NeoGiga\CatalogImport\Models\ImportRowError;
use NeoGiga\CatalogImport\Models\CatalogSource;

class XmlParserService
{
    protected array $mappingConfig;
    protected int $batchSize = 500;
    
    // Security: Disable external entities to prevent XXE attacks
    protected array $xmlOptions = [
        'disable_entities' => true,
        'load_external_dtd' => false,
        'load_external_entities' => false
    ];

    /**
     * Parse XML file or string into staging rows
     */
    public function parse(ImportFile $importFile, CatalogSource $source, array $mappingConfig): array
    {
        $this->mappingConfig = $mappingConfig;
        $filePath = $importFile->getFullPath();
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Import file not found: {$filePath}");
        }

        $stats = [
            'total_rows' => 0,
            'valid_rows' => 0,
            'error_rows' => 0,
            'batches_created' => 0
        ];

        // Security: Load XML with disabled external entities
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        
        // Prevent XXE by disabling external entity loading
        $previousLibXMLEntityLoader = libxml_disable_entity_loader(true);
        
        try {
            $loaded = $dom->load($filePath, LIBXML_NONET | LIBXML_NOENT);
            
            if (!$loaded) {
                throw new \RuntimeException("Invalid XML: " . libxml_get_last_error()->message);
            }
        } finally {
            // Restore previous state
            libxml_disable_entity_loader($previousLibXMLEntityLoader);
        }

        $xpath = new DOMXPath($dom);
        
        // Register namespaces from document
        $namespaces = $dom->documentElement->getDocNamespaces();
        foreach ($namespaces as $prefix => $uri) {
            if ($prefix === '') {
                $xpath->registerNamespace('default', $uri);
            } else {
                $xpath->registerNamespace($prefix, $uri);
            }
        }

        // Get row selector from mapping (e.g., "//products/product")
        $rowSelector = $mappingConfig['row_selector'] ?? '//product';
        $productNodes = $xpath->query($rowSelector);

        if (!$productNodes) {
            throw new \RuntimeException("No products found using XPath: {$rowSelector}");
        }

        $buffer = [];
        $rowIndex = 0;

        foreach ($productNodes as $node) {
            $rowIndex++;
            $stats['total_rows']++;

            try {
                $normalizedData = $this->extractNodeData($xpath, $node, $rowIndex);
                
                $validationErrors = $this->validateRow($normalizedData);
                
                if (!empty($validationErrors)) {
                    $this->logError($importFile->id, $rowIndex, $validationErrors, $normalizedData);
                    $stats['error_rows']++;
                    continue;
                }

                $buffer[] = $normalizedData;

                if (count($buffer) >= $this->batchSize) {
                    $this->flushBuffer($importFile->id, $source->id, $buffer);
                    $stats['batches_created']++;
                    $stats['valid_rows'] += count($buffer);
                    $buffer = [];
                }

            } catch (\Exception $e) {
                $this->logError($importFile->id, $rowIndex, ['system_error' => $e->getMessage()], []);
                $stats['error_rows']++;
            }
        }

        // Flush remaining
        if (!empty($buffer)) {
            $this->flushBuffer($importFile->id, $source->id, $buffer);
            $stats['batches_created']++;
            $stats['valid_rows'] += count($buffer);
        }

        Log::info("XML Parse Complete", [
            'file_id' => $importFile->id,
            'stats' => $stats
        ]);

        return $stats;
    }

    /**
     * Extract data from a single product node using XPath mappings
     */
    protected function extractNodeData(DOMXPath $xpath, \DOMNode $node, int $rowIndex): array
    {
        $normalized = [
            'source_row_index' => $rowIndex,
            'raw_xml' => $node->C14N(), // Canonical XML for audit
            'mapped_data' => []
        ];

        foreach ($this->mappingConfig['field_mappings'] ?? [] as $targetField => $xpathExpr) {
            // Handle nested specifications
            if (Str::startsWith($targetField, 'spec_')) {
                $specs = $this->extractSpecifications($xpath, $node, $this->mappingConfig['spec_config'] ?? []);
                $normalized['mapped_data']['attributes'] = $specs;
                continue;
            }

            // Handle repeating nodes (images, datasheets)
            if (Str::startsWith($targetField, 'media_')) {
                $media = $this->extractMedia($xpath, $node, $xpathExpr);
                $normalized['mapped_data'][$targetField] = $media;
                continue;
            }

            // Standard field extraction
            $result = $xpath->evaluate($xpathExpr, $node);
            
            if ($result instanceof \DOMNodeList) {
                $value = $result->length > 0 ? $result->item(0)->textContent : null;
            } elseif (is_string($result)) {
                $value = $result;
            } else {
                $value = null;
            }

            if ($value !== null) {
                $normalized['mapped_data'][$targetField] = trim($value);
            }
        }

        return $normalized;
    }

    /**
     * Extract specification key-value pairs from nested XML structure
     */
    protected function extractSpecifications(DOMXPath $xpath, \DOMNode $node, array $specConfig): array
    {
        $specs = [];
        $specSelector = $specConfig['selector'] ?? './/specification';
        $nameSelector = $specConfig['name_xpath'] ?? '@name';
        $valueSelector = $specConfig['value_xpath'] ?? 'text()';
        $unitSelector = $specConfig['unit_xpath'] ?? '@unit';

        $specNodes = $xpath->query($specSelector, $node);
        
        foreach ($specNodes as $specNode) {
            $name = $xpath->evaluate($nameSelector, $specNode);
            $value = $xpath->evaluate($valueSelector, $specNode);
            $unit = $xpath->evaluate($unitSelector, $specNode);

            if ($name && $value !== null) {
                $specs[] = [
                    'name' => (string)$name,
                    'value' => (string)$value,
                    'unit' => $unit ? (string)$unit : null
                ];
            }
        }

        return $specs;
    }

    /**
     * Extract media URLs (images, datasheets) from repeating nodes
     */
    protected function extractMedia(DOMXPath $xpath, \DOMNode $node, string $xpathExpr): array
    {
        $mediaNodes = $xpath->query($xpathExpr, $node);
        $media = [];

        foreach ($mediaNodes as $mediaNode) {
            $url = $mediaNode->getAttribute('href') ?: $mediaNode->textContent;
            $type = $mediaNode->getAttribute('type') ?: 'unknown';
            
            if (!empty($url)) {
                $media[] = [
                    'url' => trim($url),
                    'type' => $type
                ];
            }
        }

        return $media;
    }

    /**
     * Validate required fields
     */
    protected function validateRow(array $normalized): array
    {
        $errors = [];
        $data = $normalized['mapped_data'];

        if (empty($data['manufacturer_part_number'] ?? '')) {
            $errors['mpn_missing'] = 'Manufacturer Part Number is required';
        }

        if (empty($data['manufacturer'] ?? '') && empty($data['manufacturer_id'] ?? '')) {
            $errors['manufacturer_missing'] = 'Manufacturer Name or ID is required';
        }

        return $errors;
    }

    /**
     * Insert buffered rows into DB
     */
    protected function flushBuffer(int $fileId, int $sourceId, array $buffer): void
    {
        $insertData = array_map(function ($row) use ($fileId, $sourceId) {
            return [
                'import_file_id' => $fileId,
                'catalog_source_id' => $sourceId,
                'row_index' => $row['source_row_index'],
                'status' => 'pending',
                'payload' => json_encode($row),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $buffer);

        ImportRow::insert($insertData);
    }

    protected function logError(int $fileId, int $rowIndex, array $errors, array $rawData): void
    {
        ImportRowError::create([
            'import_file_id' => $fileId,
            'row_index' => $rowIndex,
            'error_type' => 'validation',
            'message' => json_encode($errors),
            'raw_data' => json_encode($rawData)
        ]);
    }
}
