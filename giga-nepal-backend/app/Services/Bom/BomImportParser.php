<?php

namespace App\Services\Bom;

/**
 * Parses a bill-of-materials from CSV text or pasted text into normalized lines.
 *
 * Understands the common electronics-BOM exports (LCSC / EasyEDA / JLCPCB /
 * Altium / KiCad) by mapping a wide set of header aliases to canonical fields.
 * When no recognizable header is present it falls back to a positional guess
 * (first column = part number, first numeric-looking column = quantity).
 *
 * Pure and side-effect free: no DB, no filesystem. Feed it a string.
 */
class BomImportParser
{
    /** Canonical field => header aliases (lower-cased, spaces/underscores stripped). */
    private const ALIASES = [
        'mpn' => [
            'mpn', 'manufacturerpartnumber', 'mfrpartnumber', 'mfrpart', 'mfgpart',
            'partnumber', 'part', 'partno', 'partno.', 'mfrpart#', 'manufacturerpart#',
            'supplierpartnumber', 'pn', 'part_num', 'partnum', 'mfg_part_no',
            'componentpartnumber', 'componentpart', 'device', 'devicetype',
        ],
        'manufacturer' => [
            'manufacturer', 'mfr', 'mfg', 'brand', 'make', 'vendor',
            'manufacturername', 'mfrname', 'mfgname', 'brandname', 'vender',
            'supplier', 'suppliername',
        ],
        'description' => [
            'description', 'comment', 'value', 'desc', 'name', 'component',
            'componentdescription', 'compdesc', 'notes', 'partdescription',
            'deviceDescription', 'item',
        ],
        'quantity' => [
            'quantity', 'qty', 'qnty', 'count', 'amount', 'quantityperboard',
            'quantityrequired', 'qtyrequired', 'qtyreq', 'num', 'number',
            'quantityneeded', 'qtyneeded',
        ],
        'raw_reference' => [
            'designator', 'designators', 'reference', 'references', 'refdes',
            'ref', 'refs', 'location', 'locations', 'referenceDesignator',
            'refdesignator', 'refdesignators', 'refdes',
        ],
        'package' => [
            'package', 'footprint', 'pkg', 'case', 'packagesize',
            'footprintreference', 'pattern',
        ],
        'value' => [
            'value', 'val', 'parametervalue', 'param', 'setting',
        ],
        'tolerance' => [
            'tolerance', 'tol', 'tolerancevalue',
        ],
        'voltage' => [
            'voltagerating', 'voltagerated', 'voltage', 'vmax', 'vrated',
            'ratedvoltage', 'workingvoltage',
        ],
    ];

    /**
     * @return array{lines: array<int, array<string, mixed>>, delimiter: string, mapped: array<string,int>, has_header: bool}
     */
    public function parse(string $content): array
    {
        $rows = $this->toRows($content, $delimiter);
        $rows = array_values(array_filter($rows, fn ($r) => $this->rowHasContent($r)));

        if ($rows === []) {
            return ['lines' => [], 'delimiter' => $delimiter, 'mapped' => [], 'has_header' => false];
        }

        $mapped = $this->mapHeader($rows[0]);
        $hasHeader = $mapped !== [];
        $dataRows = $hasHeader ? array_slice($rows, 1) : $rows;

        if (! $hasHeader) {
            $mapped = $this->guessPositional($dataRows);
        }

        $lines = [];
        $lineNo = 0;
        foreach ($dataRows as $row) {
            $mpn = $this->cell($row, $mapped, 'mpn');
            $description = $this->cell($row, $mapped, 'description');

            // Skip rows with neither a part number nor a description.
            if (($mpn === null || $mpn === '') && ($description === null || $description === '')) {
                continue;
            }

            $lineNo++;
            $lines[] = [
                'line_no' => $lineNo,
                'mpn' => $this->clip($mpn, 190),
                'manufacturer' => $this->clip($this->cell($row, $mapped, 'manufacturer'), 190),
                'description' => $this->clip($description, 190),
                'raw_reference' => $this->clip($this->cell($row, $mapped, 'raw_reference'), 190),
                'quantity' => $this->quantity($this->cell($row, $mapped, 'quantity')),
            ];
        }

        return ['lines' => $lines, 'delimiter' => $delimiter, 'mapped' => $mapped, 'has_header' => $hasHeader];
    }

    /** Split text into rows of cells, auto-detecting the delimiter. */
    private function toRows(string $content, ?string &$delimiter): array
    {
        $content = str_replace(["\r\n", "\r"], "\n", trim($content));
        $firstLine = strtok($content, "\n") ?: '';
        $delimiter = $this->detectDelimiter($firstLine);

        $rows = [];
        foreach (explode("\n", $content) as $line) {
            if (trim($line) === '') {
                continue;
            }
            // Explicit enclosure/escape: silences the PHP 8.4 deprecation and
            // pins RFC-4180 / Excel behaviour (quotes escaped by doubling, no backslash).
            $rows[] = array_map('trim', str_getcsv($line, $delimiter, '"', ''));
        }

        return $rows;
    }

    private function detectDelimiter(string $line): string
    {
        $counts = [
            ',' => substr_count($line, ','),
            "\t" => substr_count($line, "\t"),
            ';' => substr_count($line, ';'),
            '|' => substr_count($line, '|'),
        ];
        arsort($counts);
        $best = array_key_first($counts);

        return $counts[$best] > 0 ? $best : ',';
    }

    /** @return array<string,int> canonical field => column index */
    private function mapHeader(array $header): array
    {
        $mapped = [];
        foreach ($header as $index => $cell) {
            $key = $this->normalizeHeader($cell);
            foreach (self::ALIASES as $canonical => $aliases) {
                if (isset($mapped[$canonical])) {
                    continue;
                }
                if (in_array($key, $aliases, true)) {
                    $mapped[$canonical] = $index;
                }
            }
        }

        // A header must expose at least a part number or description to be trusted.
        if (! isset($mapped['mpn']) && ! isset($mapped['description'])) {
            return [];
        }

        return $mapped;
    }

    /** No header: assume col 0 is the part; find a mostly-numeric column for qty. */
    private function guessPositional(array $dataRows): array
    {
        $mapped = ['mpn' => 0];
        $width = 0;
        foreach ($dataRows as $r) {
            $width = max($width, count($r));
        }

        for ($col = 1; $col < $width; $col++) {
            $numeric = 0;
            $total = 0;
            foreach ($dataRows as $r) {
                if (! array_key_exists($col, $r) || trim((string) $r[$col]) === '') {
                    continue;
                }
                $total++;
                if (preg_match('/^\d+(\.\d+)?$/', trim((string) $r[$col]))) {
                    $numeric++;
                }
            }
            if ($total > 0 && $numeric === $total) {
                $mapped['quantity'] = $col;
                break;
            }
        }

        return $mapped;
    }

    private function cell(array $row, array $mapped, string $field): ?string
    {
        if (! isset($mapped[$field])) {
            return null;
        }
        $index = $mapped[$field];

        return array_key_exists($index, $row) ? trim((string) $row[$index]) : null;
    }

    private function quantity(?string $raw): float
    {
        if ($raw === null || $raw === '') {
            return 1.0;
        }
        // Keep digits and a decimal point only (strip units like "pcs").
        $clean = preg_replace('/[^0-9.]/', '', $raw);
        if ($clean === '' || ! is_numeric($clean)) {
            return 1.0;
        }
        $value = (float) $clean;

        return $value > 0 ? $value : 1.0;
    }

    private function normalizeHeader(string $value): string
    {
        return preg_replace('/[\s_]+/', '', strtolower(trim($value)));
    }

    private function rowHasContent(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return true;
            }
        }

        return false;
    }

    private function clip(?string $value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_substr($value, 0, $max);
    }

    /**
     * Parse a pasted table (tab-separated or multi-space separated).
     */
    public function parsePastedTable(string $content): array
    {
        return $this->parse($content);
    }

    /**
     * Parse from an array of rows (already split).
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array{lines: array<int, array<string, mixed>>, mapped: array<string,int>, has_header: bool}
     */
    public function parseRows(array $rows): array
    {
        $rows = array_values(array_filter($rows, fn ($r) => $this->rowHasContent($r)));

        if ($rows === []) {
            return ['lines' => [], 'mapped' => [], 'has_header' => false];
        }

        $mapped = $this->mapHeader($rows[0]);
        $hasHeader = $mapped !== [];
        $dataRows = $hasHeader ? array_slice($rows, 1) : $rows;

        if (! $hasHeader) {
            $mapped = $this->guessPositional($dataRows);
        }

        $lines = [];
        $lineNo = 0;
        foreach ($dataRows as $row) {
            $mpn = $this->cell($row, $mapped, 'mpn');
            $description = $this->cell($row, $mapped, 'description');

            if (($mpn === null || $mpn === '') && ($description === null || $description === '')) {
                continue;
            }

            $lineNo++;
            $lines[] = [
                'line_no' => $lineNo,
                'mpn' => $this->clip($mpn, 190),
                'manufacturer' => $this->clip($this->cell($row, $mapped, 'manufacturer'), 190),
                'description' => $this->clip($description, 190),
                'raw_reference' => $this->clip($this->cell($row, $mapped, 'raw_reference'), 190),
                'quantity' => $this->quantity($this->cell($row, $mapped, 'quantity')),
                'package' => $this->clip($this->cell($row, $mapped, 'package'), 50),
                'value' => $this->clip($this->cell($row, $mapped, 'value'), 100),
                'tolerance' => $this->clip($this->cell($row, $mapped, 'tolerance'), 20),
                'voltage' => $this->clip($this->cell($row, $mapped, 'voltage'), 20),
            ];
        }

        return ['lines' => $lines, 'mapped' => $mapped, 'has_header' => $hasHeader];
    }

    /**
     * Detect the format of the input content.
     */
    public function detectFormat(string $content): string
    {
        $trimmed = trim($content);

        // Check for JSON
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return 'json';
            }
        }

        // Check for XML
        if (str_starts_with($trimmed, '<?xml') || str_starts_with($trimmed, '<')) {
            return 'xml';
        }

        // Check for CSV/TSV
        $firstLine = strtok($content, "\n") ?: '';
        if (substr_count($firstLine, "\t") >= 2) {
            return 'tsv';
        }
        if (substr_count($firstLine, ',') >= 2) {
            return 'csv';
        }
        if (substr_count($firstLine, ';') >= 2) {
            return 'csv_semicolon';
        }

        // Check for markdown table
        if (preg_match('/^\|.*\|$/m', $trimmed)) {
            return 'markdown_table';
        }

        return 'text';
    }

    /**
     * Get parsing statistics.
     */
    public function getStats(array $parsed): array
    {
        $lines = $parsed['lines'] ?? [];
        $withMpn = 0;
        $withManufacturer = 0;
        $withQuantity = 0;

        foreach ($lines as $line) {
            if (! empty($line['mpn'])) {
                $withMpn++;
            }
            if (! empty($line['manufacturer'])) {
                $withManufacturer++;
            }
            if (($line['quantity'] ?? 1) > 1) {
                $withQuantity++;
            }
        }

        return [
            'total_lines' => count($lines),
            'with_mpn' => $withMpn,
            'with_manufacturer' => $withManufacturer,
            'with_quantity' => $withQuantity,
            'has_header' => $parsed['has_header'] ?? false,
            'mapped_fields' => array_keys($parsed['mapped'] ?? []),
        ];
    }

    /**
     * Merge duplicate lines (same MPN).
     */
    public function mergeDuplicates(array $lines): array
    {
        $merged = [];
        $seen = [];

        foreach ($lines as $line) {
            $key = strtoupper(trim($line['mpn'] ?? ''));

            if ($key === '') {
                $merged[] = $line;
                continue;
            }

            if (isset($seen[$key])) {
                // Add quantity to existing line
                $idx = $seen[$key];
                $merged[$idx]['quantity'] = ($merged[$idx]['quantity'] ?? 1) + ($line['quantity'] ?? 1);
                $merged[$idx]['duplicate_count'] = ($merged[$idx]['duplicate_count'] ?? 1) + 1;
            } else {
                $seen[$key] = count($merged);
                $line['duplicate_count'] = 1;
                $merged[] = $line;
            }
        }

        return $merged;
    }
}
