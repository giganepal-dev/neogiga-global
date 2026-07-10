<?php

namespace NeoGiga\CatalogImport\Services\Parsers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use NeoGiga\CatalogImport\Models\ImportFile;
use NeoGiga\CatalogImport\Models\ImportRow;
use NeoGiga\CatalogImport\Models\ImportRowError;
use NeoGiga\CatalogImport\Models\CatalogSource;
use NeoGiga\CatalogImport\Services\Mapping\FieldMapperService;

class CsvParserService
{
    protected FieldMapperService $fieldMapper;
    protected array $mappingConfig;
    protected int $batchSize = 500;
    
    // Security: Prevent CSV Formula Injection
    protected array $dangerousPrefixes = ['=', '+', '-', '@'];

    public function __construct(FieldMapperService $fieldMapper)
    {
        $this->fieldMapper = $fieldMapper;
    }

    /**
     * Parse a CSV file into staging rows
     * 
     * @param ImportFile $importFile
     * @param CatalogSource $source
     * @param array $mappingConfig Column map from admin UI
     * @return array Statistics
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

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open import file");
        }

        // Detect encoding and BOM
        $this->handleEncoding($handle);

        // Read Header
        $headers = fgetcsv($handle, 0, $this->detectDelimiter($filePath));
        if (!$headers) {
            fclose($handle);
            throw new \RuntimeException("Empty or invalid CSV file");
        }

        $headers = $this->cleanHeaders($headers);
        $columnMap = $this->resolveColumnMap($headers, $mappingConfig);

        $buffer = [];
        $rowIndex = 0;

        while (($data = fgetcsv($handle, 0, $this->detectDelimiter($filePath))) !== false) {
            $rowIndex++;
            $stats['total_rows']++;

            try {
                $normalizedData = $this->normalizeRow($data, $columnMap, $rowIndex);
                
                // Validate Required Fields
                $validationErrors = $this->validateRow($normalizedData);
                
                if (!empty($validationErrors)) {
                    $this->logError($importFile->id, $rowIndex, $validationErrors, $data);
                    $stats['error_rows']++;
                    continue;
                }

                // Security: Sanitize inputs
                $normalizedData = $this->sanitizeInputs($normalizedData);

                $buffer[] = $normalizedData;

                if (count($buffer) >= $this->batchSize) {
                    $this->flushBuffer($importFile->id, $source->id, $buffer);
                    $stats['batches_created']++;
                    $stats['valid_rows'] += count($buffer);
                    $buffer = [];
                }

            } catch (\Exception $e) {
                $this->logError($importFile->id, $rowIndex, ['system_error' => $e->getMessage()], $data);
                $stats['error_rows']++;
            }
        }

        // Flush remaining
        if (!empty($buffer)) {
            $this->flushBuffer($importFile->id, $source->id, $buffer);
            $stats['batches_created']++;
            $stats['valid_rows'] += count($buffer);
        }

        fclose($handle);
        
        Log::info("CSV Parse Complete", [
            'file_id' => $importFile->id,
            'stats' => $stats
        ]);

        return $stats;
    }

    /**
     * Normalize raw CSV row into structured array based on mapping
     */
    protected function normalizeRow(array $data, array $columnMap, int $rowIndex): array
    {
        $normalized = [
            'source_row_index' => $rowIndex,
            'raw_data' => json_encode($data), // Preserve original
            'mapped_data' => []
        ];

        foreach ($columnMap as $targetField => $sourceIndex) {
            if ($sourceIndex !== null && isset($data[$sourceIndex])) {
                $value = trim($data[$sourceIndex]);
                
                // Handle Long Format (Attribute Name/Value pairs)
                if (Str::startsWith($targetField, 'attr_')) {
                    $normalized['mapped_data']['attributes'][] = [
                        'name' => str_replace('attr_', '', $targetField),
                        'value' => $value,
                        'unit' => $data[$columnMap['attr_unit_'.$targetField] ?? -1] ?? null
                    ];
                } else {
                    $normalized['mapped_data'][$targetField] = $value;
                }
            }
        }

        return $normalized;
    }

    /**
     * Validate critical fields before staging
     */
    protected function validateRow(array $normalized): array
    {
        $errors = [];
        $data = $normalized['mapped_data'];

        if (empty($data['manufacturer_part_number'] ?? '')) {
            $errors['mpn_missing'] = 'Manufacturer Part Number is required';
        }

        if (empty($data['manufacturer_name'] ?? '') && empty($data['manufacturer_id'] ?? '')) {
            $errors['manufacturer_missing'] = 'Manufacturer Name or ID is required';
        }

        // Add more validation rules based on source requirements
        
        return $errors;
    }

    /**
     * Security: Prevent CSV Formula Injection
     */
    protected function sanitizeInputs(array $normalized): array
    {
        array_walk_recursive($normalized['mapped_data'], function (&$item) {
            if (is_string($item) && strlen($item) > 0) {
                $firstChar = substr($item, 0, 1);
                if (in_array($firstChar, $this->dangerousPrefixes)) {
                    // Escape by adding single quote or space
                    $item = "'" . $item; 
                }
            }
        });
        return $normalized;
    }

    /**
     * Insert buffered rows into DB
     */
    protected function flushBuffer(int $fileId, int $sourceId, array $buffer): void
    {
        // Bulk insert for performance
        $insertData = array_map(function ($row) use ($fileId, $sourceId) {
            return [
                'import_file_id' => $fileId,
                'catalog_source_id' => $sourceId,
                'row_index' => $row['source_row_index'],
                'status' => 'pending', // pending, processed, failed
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

    protected function detectDelimiter(string $filePath): string
    {
        // Simple heuristic: check first line for common delimiters
        $line = fgets(fopen($filePath, 'r'));
        if (substr_count($line, ';') > substr_count($line, ',')) {
            return ';';
        }
        return ',';
    }

    protected function cleanHeaders(array $headers): array
    {
        return array_map(function ($h) {
            return strtolower(trim(str_replace(["\n", "\r"], '', $h)));
        }, $headers);
    }

    protected function resolveColumnMap(array $headers, array $config): array
    {
        $map = [];
        foreach ($config as $targetField => $sourceHeader) {
            $index = array_search(strtolower($sourceHeader), $headers);
            $map[$targetField] = $index === false ? null : $index;
        }
        return $map;
    }

    protected function handleEncoding($handle): void
    {
        // Check for UTF-8 BOM and skip if present
        $bom = fread($handle, 3);
        if ($bom !== pack('CCC', 0xEF, 0xBB, 0xBF)) {
            rewind($handle);
        }
    }
}
