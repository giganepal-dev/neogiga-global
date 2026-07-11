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
            'supplierpartnumber',
        ],
        'manufacturer' => ['manufacturer', 'mfr', 'mfg', 'brand', 'make', 'vendor'],
        'description' => ['description', 'comment', 'value', 'desc', 'name', 'component'],
        'quantity' => ['quantity', 'qty', 'qnty', 'count', 'amount', 'quantityperboard'],
        'raw_reference' => [
            'designator', 'designators', 'reference', 'references', 'refdes',
            'ref', 'refs', 'location', 'locations',
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
            $rows[] = array_map('trim', str_getcsv($line, $delimiter));
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
}
