<?php

namespace App\Services\CustomerImport;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SpreadsheetCustomerReader
{
    /** @return list<string> */
    public function worksheetNames(string $path): array
    {
        $this->validateFile($path);
        $reader = IOFactory::createReaderForFile($path);

        return array_values(array_map(
            fn (array $sheet): string => (string) $sheet['worksheetName'],
            $reader->listWorksheetInfo($path),
        ));
    }

    public function preview(string $path, string $profileName, ?string $sheetName = null, ?int $limit = null): array
    {
        $data = $this->read($path, $profileName, $sheetName, $limit ?? (int) config('customer_import.preview_rows', 20));

        return [
            'file_name' => basename($path),
            'worksheets' => $data['worksheets'],
            'worksheet' => $data['worksheet'],
            'headers' => $data['headers'],
            'mapping' => $data['mapping'],
            'missing_required' => $data['missing_required'],
            'total_rows' => $data['total_rows'],
            'rows' => $data['rows'],
        ];
    }

    public function read(string $path, string $profileName, ?string $sheetName = null, ?int $rowLimit = null): array
    {
        $this->validateFile($path);
        $profile = $this->profile($profileName);
        $worksheets = $this->worksheetNames($path);
        $selected = $sheetName ?: ($profile['worksheet'] ?? null) ?: ($worksheets[0] ?? null);
        if (! $selected || ! in_array($selected, $worksheets, true)) {
            throw new InvalidArgumentException("Worksheet [{$selected}] was not found.");
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(false);
        $reader->setLoadSheetsOnly([$selected]);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheetByName($selected);
        if (! $sheet) {
            $spreadsheet->disconnectWorksheets();
            throw new InvalidArgumentException("Worksheet [{$selected}] could not be loaded.");
        }

        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $maxRows = (int) config('customer_import.max_rows', 100000);
        if (($highestRow - 1) > $maxRows) {
            $spreadsheet->disconnectWorksheets();
            throw new InvalidArgumentException("Spreadsheet exceeds the configured {$maxRows}-row limit.");
        }

        $headers = [];
        for ($column = 1; $column <= $highestColumn; $column++) {
            $headers[$column] = trim((string) $this->cellValue($sheet, $column, 1));
        }
        $mapping = $this->mapHeaders($headers, $profile);
        $missing = array_values(array_diff($profile['required'] ?? [], array_keys($mapping)));

        $rows = [];
        $lastRow = $rowLimit === null ? $highestRow : min($highestRow, 1 + max(0, $rowLimit));
        for ($rowNumber = 2; $rowNumber <= $lastRow; $rowNumber++) {
            $raw = [];
            $mapped = [];
            $hasValue = false;
            foreach ($headers as $column => $header) {
                if ($header === '') {
                    continue;
                }
                $value = $this->cellValue($sheet, $column, $rowNumber);
                $raw[$header] = $value;
                $hasValue = $hasValue || $value !== null && $value !== '';
            }
            if (! $hasValue) {
                continue;
            }
            foreach ($mapping as $target => $column) {
                $mapped[$target] = $this->cellValue($sheet, $column, $rowNumber);
            }
            $rows[] = ['row_number' => $rowNumber, 'raw' => $raw, 'mapped' => $mapped];
        }

        $spreadsheet->disconnectWorksheets();

        return [
            'worksheets' => $worksheets,
            'worksheet' => $selected,
            'headers' => array_values($headers),
            'mapping' => collect($mapping)->mapWithKeys(fn (int $column, string $target) => [$target => $headers[$column]])->all(),
            'mapping_columns' => $mapping,
            'missing_required' => $missing,
            'total_rows' => max(0, $highestRow - 1),
            'rows' => $rows,
        ];
    }

    public function validateFile(string $path): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException('Customer import file is missing or unreadable.');
        }
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, config('customer_import.allowed_extensions', []), true)) {
            throw new InvalidArgumentException("Unsupported spreadsheet extension [{$extension}].");
        }
        $maxBytes = (int) config('customer_import.max_file_size_kb', 20480) * 1024;
        if (filesize($path) > $maxBytes) {
            throw new InvalidArgumentException('Customer import file exceeds the configured size limit.');
        }

        $expectedReader = [
            'xlsx' => 'Xlsx',
            'xls' => 'Xls',
            'csv' => 'Csv',
            'ods' => 'Ods',
        ][$extension] ?? null;

        try {
            $identifiedReader = IOFactory::identify($path);
        } catch (\Throwable) {
            throw new InvalidArgumentException('Customer import file content is not a readable spreadsheet.');
        }

        if ($identifiedReader !== $expectedReader) {
            throw new InvalidArgumentException("Spreadsheet content does not match its .{$extension} extension.");
        }
    }

    private function cellValue(Worksheet $sheet, int $column, int $row): mixed
    {
        $cell = $sheet->getCell([$column, $row]);
        $value = $cell->getValue();
        if ($cell->getDataType() === DataType::TYPE_FORMULA) {
            return (string) $value;
        }
        if (is_numeric($value) && ExcelDate::isDateTime($cell)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    /** @param array<int, string> $headers */
    private function mapHeaders(array $headers, array $profile): array
    {
        $mapped = [];
        foreach ($headers as $column => $header) {
            foreach (($profile['columns'] ?? []) as $source => $target) {
                if ($this->headerKey($source) === $this->headerKey($header)) {
                    $mapped[$target] = $column;
                }
            }
            foreach (($profile['aliases'] ?? []) as $target => $aliases) {
                foreach ($aliases as $alias) {
                    if ($this->headerKey($alias) === $this->headerKey($header)) {
                        $mapped[$target] = $column;
                    }
                }
            }
        }

        return $mapped;
    }

    private function headerKey(string $header): string
    {
        return mb_strtolower(trim((string) preg_replace('/[\s.]+$/u', '', preg_replace('/\s+/u', ' ', $header))));
    }

    private function profile(string $profileName): array
    {
        $profile = config("customer_import.profiles.{$profileName}");
        if (! is_array($profile)) {
            throw new InvalidArgumentException("Customer import profile [{$profileName}] is not configured.");
        }

        return $profile;
    }
}
