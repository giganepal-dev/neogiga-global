<?php

namespace App\Services\Product;

use Illuminate\Support\Facades\Cache;

/**
 * Centralized MPN normalization and matching engine.
 *
 * Handles common MPN variations: case, whitespace, quotes, hyphens,
 * slashes, packaging suffixes, temperature grades, RoHS marks, etc.
 * Stores raw input, normalized output, detected manufacturer, and
 * match confidence for traceability.
 */
class MpnNormalizationService
{
    /**
     * Known packaging/tape-and-reel suffixes to strip for matching.
     * These do not change the functional part.
     */
    private const PACKAGING_SUFFIXES = [
        'T', 'R', 'TR', 'T&R', 'T/R', 'REEL', 'REEL1000', 'REEL2500',
        'REEL500', 'REEL3000', 'TAPE', 'AMMO', 'BULK', 'BOX',
        'cut tape', 'cut tape (ct)', 'ct', 'digireel',
    ];

    /**
     * Common suffixes that indicate grade/variant but not a different part.
     */
    private const GRADE_SUFFIXES = [
        '/NOPB', 'NOPB', '/NOBR', 'NOBR', '/P', '/E', '/G3', '/G4',
        '-1', '-2', '-3', '-4', '-5',
    ];

    /**
     * Manufacturer name aliases for normalization.
     */
    private const MANUFACTURER_ALIASES = [
        'stm' => 'STMicroelectronics',
        'st' => 'STMicroelectronics',
        'stmicro' => 'STMicroelectronics',
        'ti' => 'Texas Instruments',
        'texas instruments' => 'Texas Instruments',
        'nxp' => 'NXP Semiconductors',
        'nxp semiconductors' => 'NXP Semiconductors',
        'infineon' => 'Infineon Technologies',
        'infineon technologies' => 'Infineon Technologies',
        'microchip' => 'Microchip Technology',
        'microchip technology' => 'Microchip Technology',
        'analog' => 'Analog Devices',
        'analog devices' => 'Analog Devices',
        'adi' => 'Analog Devices',
        'maxim' => 'Maxim Integrated',
        'maxim integrated' => 'Maxim Integrated',
        'onsemi' => 'onsemi',
        'on semiconductor' => 'onsemi',
        'fairchild' => 'onsemi',
        'rohm' => 'ROHM Semiconductor',
        'rohm semiconductor' => 'ROHM Semiconductor',
        'toshiba' => 'Toshiba',
        'renesas' => 'Renesas Electronics',
        'renesas electronics' => 'Renesas Electronics',
        'vishay' => 'Vishay Intertechnology',
        'vishay intertechnology' => 'Vishay Intertechnology',
        'tdk' => 'TDK Corporation',
        'murata' => 'Murata Manufacturing',
        'murata manufacturing' => 'Murata Manufacturing',
        'samsung' => 'Samsung Electro-Mechanics',
        'samsung electro-mechanics' => 'Samsung Electro-Mechanics',
        'yageo' => 'Yageo Corporation',
        'yageo corporation' => 'Yageo Corporation',
        'bourns' => 'Bourns',
        'qualcomm' => 'Qualcomm',
        'broadcom' => 'Broadcom',
        'intel' => 'Intel',
        'espressif' => 'Espressif Systems',
        'espressif systems' => 'Espressif Systems',
        'espressif' => 'Espressif Systems',
        'raspberry pi' => 'Raspberry Pi',
        'rpi' => 'Raspberry Pi',
        'arduino' => 'Arduino',
        'esd' => 'ESD',
        'liteon' => 'Lite-On',
        'lite-on' => 'Lite-On',
        'everlight' => 'Everlight Electronics',
        'everlight electronics' => 'Everlight Electronics',
        'kingbright' => 'Kingbright',
        'wurth' => 'Würth Elektronik',
        'würth' => 'Würth Elektronik',
        'wurth elektronik' => 'Würth Elektronik',
        'kemet' => 'KEMET',
        'kemet electronics' => 'KEMET',
        'avx' => 'AVX Corporation',
        'avx corporation' => 'AVX Corporation',
        'panasonic' => 'Panasonic',
        'nichicon' => 'Nichicon',
        'rubycon' => 'Rubycon',
        'nec' => 'NEC',
        'semtech' => 'Semtech',
        'diodes inc' => 'Diodes Incorporated',
        'diodes incorporated' => 'Diodes Incorporated',
        'diodes' => 'Diodes Incorporated',
        'central semi' => 'Central Semiconductor',
        'central semiconductor' => 'Central Semiconductor',
    ];

    /**
     * Common OCR/spreadsheet corruption patterns.
     */
    private const OCR_CORRECTIONS = [
        'O' => '0', // Only in specific contexts
        'l' => '1', // Only in specific contexts
    ];

    /**
     * Normalize an MPN for matching purposes.
     *
     * @return array{raw: string, normalized: string, warnings: array<int, string>}
     */
    public function normalize(?string $mpn): array
    {
        $raw = (string) ($mpn ?? '');
        $warnings = [];
        $normalized = $raw;

        if ($normalized === '') {
            return ['raw' => $raw, 'normalized' => '', 'warnings' => ['Empty MPN']];
        }

        // Step 1: Strip surrounding quotes
        $before = $normalized;
        $normalized = trim($normalized, '"\'');
        if ($normalized !== $before) {
            $warnings[] = 'Stripped surrounding quotes';
        }

        // Step 2: Normalize unicode dashes to hyphen
        $before = $normalized;
        $normalized = str_replace(['–', '—', '─', '━'], '-', $normalized);
        if ($normalized !== $before) {
            $warnings[] = 'Normalized unicode dashes';
        }

        // Step 3: Collapse multiple spaces
        $before = $normalized;
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        if ($normalized !== $before) {
            $warnings[] = 'Collapsed whitespace';
        }

        // Step 4: Strip trailing/leading whitespace
        $normalized = trim($normalized);

        // Step 5: Convert to uppercase for matching (but store original case)
        $normalized = strtoupper($normalized);

        // Step 6: Remove spaces (MPNs should not have spaces)
        $before = $normalized;
        $normalized = str_replace(' ', '', $normalized);
        if ($normalized !== $before && preg_match('/[A-Z]{3,}\s+[0-9]/', $raw)) {
            $warnings[] = 'Removed spaces from MPN';
        }

        // Step 7: Normalize common separators
        // Some MPNs use underscores or dots where hyphens are standard
        $before = $normalized;
        $normalized = str_replace(['_', '.'], '-', $normalized);
        // But restore if the original had underscores/dots as meaningful chars
        if ($normalized !== $before && ! preg_match('/^[A-Z0-9\-]+$/', $normalized)) {
            $normalized = strtoupper(str_replace(' ', '', $raw));
        }

        return [
            'raw' => $raw,
            'normalized' => $normalized,
            'warnings' => $warnings,
        ];
    }

    /**
     * Detect likely manufacturer from MPN prefix.
     */
    public function detectManufacturer(?string $mpn): ?string
    {
        if (empty($mpn)) {
            return null;
        }

        $normalized = strtoupper(trim($mpn));

        // Check cache first
        $cacheKey = 'mpn_mfr_' . md5($normalized);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = $this->matchManufacturerByPrefix($normalized);

        if ($result !== null) {
            Cache::put($cacheKey, $result, 3600);
        }

        return $result;
    }

    /**
     * Match manufacturer by MPN prefix patterns.
     */
    private function matchManufacturerByPrefix(string $mpn): ?string
    {
        $prefixes = [
            'STM32' => 'STMicroelectronics',
            'STM8' => 'STMicroelectronics',
            'STM' => 'STMicroelectronics',
            'LM' => 'Texas Instruments',
            'TL' => 'Texas Instruments',
            'TPS' => 'Texas Instruments',
            'TLV' => 'Texas Instruments',
            'ADS' => 'Texas Instruments',
            'DAC' => 'Texas Instruments',
            'TPD' => 'Texas Instruments',
            'NE555' => 'Texas Instruments',
            'OPA' => 'Texas Instruments',
            'INA' => 'Texas Instruments',
            'MCP' => 'Microchip Technology',
            'PIC' => 'Microchip Technology',
            'ATMEGA' => 'Microchip Technology',
            'ATTINY' => 'Microchip Technology',
            'ATA' => 'Microchip Technology',
            'LTC' => 'Analog Devices',
            'ADP' => 'Analog Devices',
            'ADM' => 'Analog Devices',
            'MAX' => 'Maxim Integrated',
            'ESP32' => 'Espressif Systems',
            'ESP8266' => 'Espressif Systems',
            'ESP' => 'Espressif Systems',
            'RPI' => 'Raspberry Pi',
            'BCM' => 'Broadcom',
            'RTL' => 'Realtek',
            'IMX' => 'NXP Semiconductors',
            'LPC' => 'NXP Semiconductors',
            'S32K' => 'NXP Semiconductors',
            'FRDM' => 'NXP Semiconductors',
            'MK' => 'NXP Semiconductors',
            'S9S' => 'NXP Semiconductors',
            'UCD' => 'Texas Instruments',
            'UC' => 'Texas Instruments',
            'REF' => 'Texas Instruments',
            'INA' => 'Texas Instruments',
            'TS' => 'Texas Instruments',
            'TPA' => 'Texas Instruments',
            'TAS' => 'Texas Instruments',
            'BQ' => 'Texas Instruments',
            'CC' => 'Texas Instruments',
            'PD' => 'Texas Instruments',
            'ISL' => 'Renesas Electronics',
            'R' => 'Renesas Electronics',
            'RX' => 'Renesas Electronics',
            'SH' => 'Renesas Electronics',
            'ICL' => 'Renesas Electronics',
            'EL' => 'Renesas Electronics',
            'SI' => 'Silicon Labs',
            'EFM' => 'Silicon Labs',
            'EFR' => 'Silicon Labs',
            'BG' => 'Rohm Semiconductor',
            'BD' => 'Rohm Semiconductor',
            'BA' => 'Rohm Semiconductor',
            'BU' => 'Rohm Semiconductor',
            'BM' => 'Rohm Semiconductor',
            'IP' => 'Infineon Technologies',
            'IR' => 'Infineon Technologies',
            'ICE' => 'Infineon Technologies',
            'XMC' => 'Infineon Technologies',
            'TLE' => 'Infineon Technologies',
            'BTS' => 'Infineon Technologies',
            'TDA' => 'NXP Semiconductors',
            'SAA' => 'NXP Semiconductors',
            'PCA' => 'NXP Semiconductors',
            'PT' => 'NXP Semiconductors',
            'LQ' => 'NXP Semiconductors',
            '74' => 'Texas Instruments', // 74xx logic
            'SN' => 'Texas Instruments',
            'CD' => 'Texas Instruments', // CD4000 series
            'TMS' => 'Texas Instruments',
            'TPS' => 'Texas Instruments',
            'BQ' => 'Texas Instruments',
            'ADS' => 'Texas Instruments',
            'AM' => 'Analog Devices',
            'AD' => 'Analog Devices',
            'SSM' => 'Analog Devices',
            'HMC' => 'Analog Devices',
            'ADA' => 'Analog Devices',
            'ADG' => 'Analog Devices',
            'ADP' => 'Analog Devices',
            'DG' => 'Analog Devices',
            'DS' => 'Maxim Integrated',
            'MAX' => 'Maxim Integrated',
            'ADUM' => 'Analog Devices',
            'ADE' => 'Analog Devices',
            'ADF' => 'Analog Devices',
            'ADXL' => 'Analog Devices',
            'ADG' => 'Analog Devices',
            'SMB' => 'Vishay Intertechnology',
            'SI' => 'Vishay Intertechnology',
            'BAT' => 'Vishay Intertechnology',
            'BZX' => 'NXP Semiconductors',
            '1N4' => 'onsemi',
            '2N' => 'onsemi',
            'MMSZ' => 'onsemi',
            'MBR' => 'onsemi',
            'NCP' => 'onsemi',
            'NCV' => 'onsemi',
            'CAT' => 'onsemi',
            'ESD' => 'onsemi',
            'PESD' => 'Nexperia',
            '74AUC' => 'Nexperia',
            '74LVC' => 'Nexperia',
            '74HC' => 'Nexperia',
            '74HCT' => 'Nexperia',
            '74AHC' => 'Nexperia',
            '74ABT' => 'Nexperia',
            '74LVT' => 'Nexperia',
            '74F' => 'Nexperia',
            '74S' => 'Nexperia',
            '74LS' => 'Nexperia',
            '74ALS' => 'Nexperia',
            '74ACT' => 'Nexperia',
            '74AC' => 'Nexperia',
            '74LV' => 'Nexperia',
            '74VHC' => 'Nexperia',
            '74VHCT' => 'Nexperia',
            '74AUP' => 'Nexperia',
            '74AXP' => 'Nexperia',
            '74GTL' => 'Nexperia',
            '74GTLPT' => 'Nexperia',
            '74CBT' => 'Nexperia',
            '74CBTLV' => 'Nexperia',
            '74AVC' => 'Nexperia',
            '74AVCH' => 'Nexperia',
            '74AUC' => 'Nexperia',
            '74AXC' => 'Nexperia',
            '74LVC1G' => 'Nexperia',
            '74AUC1G' => 'Nexperia',
            '74AHC1G' => 'Nexperia',
            '74LVT1G' => 'Nexperia',
            '74CBT1G' => 'Nexperia',
            '74CBTLV1G' => 'Nexperia',
            '74AVC1G' => 'Nexperia',
            '74AVCH1G' => 'Nexperia',
            '74AXC1G' => 'Nexperia',
            '74AUP1G' => 'Nexperia',
            '74AXP1G' => 'Nexperia',
            '74GTL1G' => 'Nexperia',
            '74GTLPT1G' => 'Nexperia',
            'NCJ' => 'Nexperia',
            'NCV' => 'Nexperia',
            'NCA' => 'Nexperia',
            'NCP' => 'Nexperia',
            'NCI' => 'Nexperia',
            'NCH' => 'Nexperia',
            'NCL' => 'Nexperia',
            'NCx' => 'Nexperia',
        ];

        foreach ($prefixes as $prefix => $manufacturer) {
            if (str_starts_with($mpn, $prefix)) {
                return $manufacturer;
            }
        }

        return null;
    }

    /**
     * Resolve manufacturer name from user input (handles aliases).
     */
    public function resolveManufacturer(?string $input): ?string
    {
        if (empty($input)) {
            return null;
        }

        $normalized = strtolower(trim($input));

        if (isset(self::MANUFACTURER_ALIASES[$normalized])) {
            return self::MANUFACTURER_ALIASES[$normalized];
        }

        // Check if it's already a full manufacturer name
        foreach (self::MANUFACTURER_ALIASES as $alias => $full) {
            if (strtolower($full) === $normalized) {
                return $full;
            }
        }

        return $input;
    }

    /**
     * Check if two MPNs are functionally equivalent (ignoring packaging/grade suffixes).
     */
    public function areEquivalent(string $mpn1, string $mpn2): bool
    {
        $n1 = $this->normalize($mpn1)['normalized'];
        $n2 = $this->normalize($mpn2)['normalized'];

        if ($n1 === $n2) {
            return true;
        }

        // Strip packaging suffixes and compare
        $s1 = $this->stripSuffixes($n1);
        $s2 = $this->stripSuffixes($n2);

        return $s1 === $s2;
    }

    /**
     * Strip known suffixes from MPN for functional comparison.
     */
    private function stripSuffixes(string $mpn): string
    {
        $result = $mpn;

        // Strip packaging suffixes
        foreach (self::PACKAGING_SUFFIXES as $suffix) {
            if (str_ends_with($result, '/' . strtoupper($suffix))) {
                $result = substr($result, 0, -strlen($suffix) - 1);
                break;
            }
            if (str_ends_with($result, '-' . strtoupper($suffix))) {
                $result = substr($result, 0, -strlen($suffix) - 1);
                break;
            }
        }

        // Strip grade suffixes
        foreach (self::GRADE_SUFFIXES as $suffix) {
            if (str_ends_with($result, strtoupper($suffix))) {
                $result = substr($result, 0, -strlen($suffix));
                break;
            }
        }

        return $result;
    }

    /**
     * Generate search variations for an MPN (for fuzzy matching).
     *
     * @return array<int, string>
     */
    public function searchVariations(string $mpn): array
    {
        $normalized = $this->normalize($mpn)['normalized'];
        $variations = [$normalized];

        // Without last character (for partial matches)
        if (strlen($normalized) > 4) {
            $variations[] = substr($normalized, 0, -1);
        }

        // Without packaging suffix
        $stripped = $this->stripSuffixes($normalized);
        if ($stripped !== $normalized) {
            $variations[] = $stripped;
        }

        return array_unique($variations);
    }

    /**
     * Normalize a batch of MPNs efficiently.
     *
     * @param  array<int, string>  $mpns
     * @return array<int, array{raw: string, normalized: string, warnings: array<int, string>}>
     */
    public function normalizeBatch(array $mpns): array
    {
        $results = [];
        foreach ($mpns as $index => $mpn) {
            $results[$index] = $this->normalize($mpn);
        }
        return $results;
    }

    /**
     * Look up MPN aliases from database.
     *
     * @return array<int, array{product_id: int, alias_mpn: string, alias_type: string}>
     */
    public function lookupAliases(string $mpn): array
    {
        $normalized = $this->normalize($mpn)['normalized'];
        if ($normalized === '') {
            return [];
        }

        $aliases = \App\Models\Product\MpnAlias::active()
            ->where('normalized_alias', $normalized)
            ->get(['product_id', 'alias_mpn', 'alias_type'])
            ->toArray();

        return $aliases;
    }

    /**
     * Store an MPN alias.
     */
    public function storeAlias(int $productId, string $aliasMpn, string $aliasType = 'cross_reference', ?string $source = null): \App\Models\Product\MpnAlias
    {
        $normalized = $this->normalize($aliasMpn)['normalized'];

        return \App\Models\Product\MpnAlias::updateOrCreate(
            ['product_id' => $productId, 'normalized_alias' => $normalized],
            [
                'alias_mpn' => $aliasMpn,
                'alias_type' => $aliasType,
                'source' => $source,
                'is_active' => true,
            ]
        );
    }

    /**
     * Parse a passive component description into parameters.
     *
     * Example: "10kΩ 1% 0402" -> {resistance: 10000, tolerance: 1, package: "0402"}
     *
     * @return array{type: ?string, value: ?float, unit: ?string, tolerance: ?string, package: ?string, voltage: ?string}
     */
    public function parsePassiveDescription(string $description): array
    {
        $result = [
            'type' => null,
            'value' => null,
            'unit' => null,
            'tolerance' => null,
            'package' => null,
            'voltage' => null,
        ];

        $lower = strtolower($description);

        // Detect type
        if (preg_match('/\b(resistor|resistance|ohm)\b/i', $description)) {
            $result['type'] = 'resistor';
        } elseif (preg_match('/\b(capacitor|capacitance|cap)\b/i', $description)) {
            $result['type'] = 'capacitor';
        } elseif (preg_match('/\b(inductor|inductance|coil)\b/i', $description)) {
            $result['type'] = 'inductor';
        }

        // Parse value with unit
        if (preg_match('/(\d+\.?\d*)\s*(ohm|Ω|kΩ|mΩ|GΩ|pf|nf|uf|mf|µf|mh|µh|uh)/i', $description, $matches)) {
            $value = (float) $matches[1];
            $unit = strtoupper($matches[2]);

            // Normalize unit
            if (in_array($unit, ['OHM', 'Ω'])) {
                $result['unit'] = 'Ω';
            } elseif ($unit === 'KΩ') {
                $value *= 1000;
                $result['unit'] = 'Ω';
            } elseif ($unit === 'MΩ') {
                $value *= 1000000;
                $result['unit'] = 'Ω';
            } elseif (in_array($unit, ['PF'])) {
                $result['unit'] = 'pF';
            } elseif (in_array($unit, ['NF'])) {
                $value *= 1000;
                $result['unit'] = 'pF';
            } elseif (in_array($unit, ['UF', 'µF'])) {
                $value *= 1000000;
                $result['unit'] = 'pF';
            } elseif (in_array($unit, ['MH'])) {
                $result['unit'] = 'µH';
            } elseif (in_array($unit, ['µH', 'UH'])) {
                $result['unit'] = 'µH';
            }

            $result['value'] = $value;
        }

        // Parse tolerance
        if (preg_match('/(\d+\.?\d*)%/', $description, $matches)) {
            $result['tolerance'] = $matches[1] . '%';
        } elseif (preg_match('/\b([FGJKMZ])\b/', $description, $matches)) {
            $toleranceMap = ['F' => '1%', 'G' => '2%', 'J' => '5%', 'K' => '10%', 'M' => '20%', 'Z' => '+80/-20%'];
            $result['tolerance'] = $toleranceMap[$matches[1]] ?? null;
        }

        // Parse package
        if (preg_match('/\b(0201|0402|0603|0805|1206|1210|1812|2010|2512)\b/', $description, $matches)) {
            $result['package'] = $matches[1];
        }

        // Parse voltage
        if (preg_match('/\b(\d+\.?\d*)\s*v\b/i', $description, $matches)) {
            $result['voltage'] = $matches[1] . 'V';
        }

        return $result;
    }

    /**
     * Detect if an MPN is likely a passive component.
     */
    public function isPassiveComponent(?string $mpn): bool
    {
        if (empty($mpn)) {
            return false;
        }

        $upper = strtoupper(trim($mpn));

        // Simple prefix checks for common passive components
        if (preg_match('/^R\d{3,}/', $upper)) return true;  // Resistors: R0402, R0603
        if (preg_match('/^C\d{3,}/', $upper)) return true;  // Capacitors: C0402, C0603
        if (preg_match('/^L\d{3,}/', $upper)) return true;  // Inductors: L0402, L0603
        if (str_starts_with($upper, 'RC')) return true;      // RC networks
        if (str_starts_with($upper, 'CR')) return true;      // Capacitor-resistor networks
        if (str_starts_with($upper, 'ERJ')) return true;     // Panasonic resistors
        if (str_starts_with($upper, 'CC0')) return true;     // Capacitors
        if (str_starts_with($upper, 'GRM')) return true;     // Murata capacitors
        if (str_starts_with($upper, 'BLM')) return true;     // Murata ferrite beads
        if (str_starts_with($upper, 'LQH')) return true;     // Murata inductors

        return false;
    }

    /**
     * Get normalization statistics for a batch.
     *
     * @param  array<int, string>  $mpns
     * @return array{total: int, normalized: int, with_warnings: int, empty: int, unique: int}
     */
    public function getStats(array $mpns): array
    {
        $results = $this->normalizeBatch($mpns);
        $normalized = 0;
        $withWarnings = 0;
        $empty = 0;
        $unique = [];

        foreach ($results as $result) {
            if ($result['normalized'] !== '') {
                $normalized++;
                $unique[$result['normalized']] = true;
            } else {
                $empty++;
            }
            if (! empty($result['warnings'])) {
                $withWarnings++;
            }
        }

        return [
            'total' => count($mpns),
            'normalized' => $normalized,
            'with_warnings' => $withWarnings,
            'empty' => $empty,
            'unique' => count($unique),
        ];
    }
}
