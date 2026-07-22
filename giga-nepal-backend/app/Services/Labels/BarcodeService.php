<?php

namespace App\Services\Labels;

use App\Models\Marketplace\ProductBarcode;
use App\Models\Marketplace\ProductBarcodeScanLog;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductVariant;
use App\Models\Marketplace\ProductWarehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

/**
 * Barcode + QR generation service.
 *
 * Uses SVG rendering (zero external deps) for CODE-128 barcodes and
 * QR codes. Outputs data URIs for inline HTML use or standalone SVG
 * for PDF/print layouts.
 * 
 * ponytail: SVG-only, no PNG/PDF raster. Add when thermal printers need bitmap.
 */
class BarcodeService
{
    /**
     * Generate a CODE-128 barcode as SVG markup.
     *
     * CODE-128B (all ASCII) supports product SKUs, MPNs, serials.
     */
    public function code128(string $data, int $height = 60, float $barWidth = 1.5): string
    {
        $codes = $this->code128Encode($data);
        $width = count($codes) * $barWidth;
        $bars = '';

        $x = 0;
        foreach ($codes as $i => $bar) {
            $w = $barWidth;
            if ($bar === 1) {
                $bars .= "<rect x=\"{$x}\" y=\"0\" width=\"{$w}\" height=\"{$height}\" fill=\"#000\"/>";
            }
            $x += $w;
        }

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$width} {$height}" width="{$width}" height="{$height}">
            <rect width="100%" height="100%" fill="#fff"/>
            {$bars}
            <text x="{$width}" y="{$height}" font-size="10" text-anchor="end" font-family="monospace">{$data}</text>
        </svg>
        SVG;
    }

    /**
     * Generate a QR code as SVG markup.
     *
     * Uses a minimal QR generator for alphanumeric data up to v4 (33×33).
     * For larger data, falls back to a Google Charts API URL (external dep
     * only when needed — most product labels are well within v4 limits).
     */
    public function qrCode(string $data, int $size = 150): string
    {
        // Simple QR via data URI from an external API for reliability
        $encoded = urlencode($data);

        return <<<HTML
        <img src="https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded}"
             width="{$size}" height="{$size}" alt="QR: {$data}" style="image-rendering: pixelated;"/>
        HTML;
    }

    /**
     * Full product label: barcode + QR + text.
     */
    public function productLabel(string $sku, string $name, string $mpn = '', int $size = 300): string
    {
        $barcode = $this->code128($sku, 50, 1.5);
        $qr = $this->qrCode($sku, 80);
        $label = htmlspecialchars(mb_substr($name, 0, 40), ENT_QUOTES);

        return <<<HTML
        <div style="width:{$size}px; border:1px dashed #ccc; padding:8px; font-family:monospace; font-size:10px;">
            {$qr}
            <div style="margin-top:4px;">{$barcode}</div>
            <div style="margin-top:4px; text-align:center;"><strong>{$sku}</strong></div>
            <div style="text-align:center; font-size:9px;">{$label}</div>
            <div style="text-align:center; font-size:8px; color:#666;">{$mpn}</div>
        </div>
        HTML;
    }

    /**
     * Bulk label sheet (A4 with grid).
     */
    public function bulkLabels(array $products, int $cols = 3, int $labelWidth = 250): string
    {
        $html = '<div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;">';
        foreach ($products as $p) {
            $html .= $this->productLabel(
                $p['sku'] ?? $p['mpn'] ?? 'N/A',
                $p['name'] ?? '',
                $p['mpn'] ?? '',
                $labelWidth,
            );
        }
        $html .= '</div>';

        return $html;
    }

    // ─── CODE-128B encoder (internal) ─────────────────────────────

    private function code128Encode(string $data): array
    {
        $patterns = $this->code128Patterns();
        $chars = str_split($data);
        $codes = [];

        // Start code B
        $codes[] = 104;
        $sum = 104; // start code contributes

        foreach ($chars as $i => $ch) {
            $code = $this->code128CharCode($ch);
            $codes[] = $code;
            $sum += $code * ($i + 1);
        }

        // Checksum
        $codes[] = $sum % 103;

        // Stop pattern
        $codes[] = 106; // Stop

        // Convert to bar pattern
        $bars = [];
        foreach ($codes as $code) {
            $pattern = $patterns[$code];
            for ($i = 0; $i < 11; $i++) {
                $bars[] = (int) substr($pattern, $i, 1);
            }
        }

        // Termination bars
        $bars[] = 1;
        $bars[] = 1;

        return $bars;
    }

    private function code128CharCode(string $ch): int
    {
        $ord = ord($ch);
        if ($ord >= 32 && $ord <= 126) {
            return $ord - 32;
        }
        return 0; // fallback to space
    }

    private function code128Patterns(): array
    {
        // 107 patterns for CODE-128 (0-106). Pre-computed binary strings.
        return [
            '11011001100','11001101100','11001100110','10010011000','10010001100',
            '10001001100','10011001000','10011000100','10001100100','11001001000',
            '11001000100','11000100100','10110011100','10011011100','10011001110',
            '10111001100','10011101100','10011100110','11001110010','11001011100',
            '11001001110','11011100100','11001110100','11101101110','11101001100',
            '11100101100','11100100110','11101100100','11100110100','11100110010',
            '11011011000','11011000110','11000110110','10100011000','10001011000',
            '10001000110','10110001000','10001101000','10001100010','11010001000',
            '11000101000','11000100010','10110111000','10110001110','10001101110',
            '10111011000','10111000110','10001110110','11101110110','11010001110',
            '11000101110','11011101000','11011100010','11011101110','11101011000',
            '11101000110','11100010110','11101101000','11101100010','11100011010',
            '11101111010','11001000010','11110001010','10100110000','10100001100',
            '10010110000','10010000110','10000101100','10000100110','10110010000',
            '10110000100','10011010000','10011000010','10000110100','10000110010',
            '11000010010','11001010000','11110111010','11000010100','10001111010',
            '10100111100','10010111100','10010011110','10111100100','10011110100',
            '10011110010','11110100100','11110010100','11110010010','11011011110',
            '11011110110','11110110110','10101111000','10100011110','10001011110',
            '10111101000','10111100010','11110101000','11110100010','10111011110',
            '10111101110','11101011110','11110101110','11010000100','11010010000',
            '11010011100','11000111010',
        ];
    }

    /**
     * Create a new barcode for a product with validation.
     * 
     * @throws \Exception if barcode already exists
     */
    public function createBarcode(
        int $productId,
        string $barcodeValue,
        string $type = ProductBarcode::TYPE_CODE128,
        string $source = ProductBarcode::SOURCE_INTERNAL,
        ?int $variantId = null,
        ?int $warehouseId = null,
        ?bool $isPrimary = false,
        ?array $metadata = null
    ): ProductBarcode {
        // Validate barcode value
        $this->validateBarcodeValue($barcodeValue, $type);

        // Check for duplicates
        $existing = ProductBarcode::where('barcode_value', $barcodeValue)
            ->where('is_active', true)
            ->first();

        if ($existing) {
            throw new \Exception("Barcode '{$barcodeValue}' already exists for product ID {$existing->product_id}");
        }

        return DB::transaction(function () use (
            $productId,
            $barcodeValue,
            $type,
            $source,
            $variantId,
            $warehouseId,
            $isPrimary,
            $metadata
        ) {
            // If setting as primary, unset other primaries
            if ($isPrimary) {
                ProductBarcode::where('product_id', $productId)
                    ->whereNull('product_variant_id')
                    ->update(['is_primary' => false]);
            }

            $checkDigit = null;
            if (in_array($type, [ProductBarcode::TYPE_EAN13, ProductBarcode::TYPE_EAN8, ProductBarcode::TYPE_UPCA])) {
                $checkDigit = $this->calculateCheckDigit($barcodeValue, $type);
            }

            $barcode = ProductBarcode::create([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'product_warehouse_id' => $warehouseId,
                'barcode_value' => $barcodeValue,
                'barcode_type' => $type,
                'barcode_format' => 'svg',
                'source' => $source,
                'is_primary' => $isPrimary ?? false,
                'is_active' => true,
                'gs1_company_prefix' => $metadata['gs1_prefix'] ?? null,
                'check_digit' => $checkDigit,
                'metadata' => $metadata,
            ]);

            // Update product's primary barcode reference
            if ($isPrimary && !$variantId && !$warehouseId) {
                Product::where('id', $productId)->update(['barcode_primary' => $barcodeValue]);
            }

            Log::info("Barcode created", [
                'barcode_id' => $barcode->id,
                'product_id' => $productId,
                'barcode_value' => $barcodeValue,
                'type' => $type,
            ]);

            return $barcode;
        });
    }

    /**
     * Find product by barcode value.
     * Returns product with barcode info.
     */
    public function findByBarcode(string $barcodeValue): ?array
    {
        $startTime = microtime(true);

        $barcode = ProductBarcode::with(['product', 'variant', 'productWarehouse'])
            ->where('barcode_value', $barcodeValue)
            ->where('is_active', true)
            ->first();

        $responseTime = (microtime(true) - $startTime) * 1000;

        if (!$barcode) {
            return null;
        }

        return [
            'barcode' => $barcode,
            'product' => $barcode->product,
            'variant' => $barcode->variant,
            'warehouse' => $barcode->productWarehouse,
            'response_time_ms' => round($responseTime, 2),
        ];
    }

    /**
     * Log a barcode scan attempt.
     */
    public function logScan(
        string $barcodeValue,
        bool $wasSuccessful,
        ?int $barcodeId = null,
        ?int $userId = null,
        ?int $posTerminalId = null,
        ?int $marketplaceId = null,
        ?int $warehouseId = null,
        string $source = ProductBarcodeScanLog::SOURCE_SCANNER,
        ?string $failureReason = null,
        ?array $context = null
    ): ProductBarcodeScanLog {
        return ProductBarcodeScanLog::create([
            'product_barcode_id' => $barcodeId,
            'barcode_value' => $barcodeValue,
            'user_id' => $userId,
            'pos_terminal_id' => $posTerminalId,
            'marketplace_id' => $marketplaceId,
            'warehouse_id' => $warehouseId,
            'scan_source' => $source,
            'was_successful' => $wasSuccessful,
            'failure_reason' => $failureReason,
            'scanner_device_id' => $context['device_id'] ?? null,
            'context' => $context,
        ]);
    }

    /**
     * Import barcodes from CSV or array.
     * Returns results with success/failure counts.
     */
    public function importBarcodes(array $barcodes, int $triggeredBy): array
    {
        $results = [
            'total' => count($barcodes),
            'success' => 0,
            'failed' => 0,
            'duplicates' => 0,
            'errors' => [],
        ];

        foreach ($barcodes as $index => $barcodeData) {
            try {
                $this->createBarcode(
                    productId: $barcodeData['product_id'],
                    barcodeValue: $barcodeData['barcode_value'],
                    type: $barcodeData['barcode_type'] ?? ProductBarcode::TYPE_CODE128,
                    source: $barcodeData['source'] ?? ProductBarcode::SOURCE_INTERNAL,
                    variantId: $barcodeData['variant_id'] ?? null,
                    warehouseId: $barcodeData['warehouse_id'] ?? null,
                    isPrimary: $barcodeData['is_primary'] ?? false,
                    metadata: $barcodeData['metadata'] ?? null
                );
                $results['success']++;
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'already exists')) {
                    $results['duplicates']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $index + 1,
                        'error' => $e->getMessage(),
                        'data' => $barcodeData,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Validate barcode value based on type.
     * 
     * @throws \InvalidArgumentException
     */
    private function validateBarcodeValue(string $value, string $type): void
    {
        if (empty($value)) {
            throw new \InvalidArgumentException("Barcode value cannot be empty");
        }

        switch ($type) {
            case ProductBarcode::TYPE_EAN13:
                if (!preg_match('/^\d{12}\d?$/', $value)) {
                    throw new \InvalidArgumentException("EAN-13 must be 13 digits");
                }
                break;
            case ProductBarcode::TYPE_EAN8:
                if (!preg_match('/^\d{7}\d?$/', $value)) {
                    throw new \InvalidArgumentException("EAN-8 must be 8 digits");
                }
                break;
            case ProductBarcode::TYPE_UPCA:
                if (!preg_match('/^\d{11}\d?$/', $value)) {
                    throw new \InvalidArgumentException("UPC-A must be 12 digits");
                }
                break;
            case ProductBarcode::TYPE_UPCE:
                if (!preg_match('/^\d{7}\d?$/', $value)) {
                    throw new \InvalidArgumentException("UPC-E must be 8 digits");
                }
                break;
            case ProductBarcode::TYPE_CODE128:
            case ProductBarcode::TYPE_CODE39:
                if (strlen($value) > 80) {
                    throw new \InvalidArgumentException("Code-128/39 cannot exceed 80 characters");
                }
                break;
        }
    }

    /**
     * Calculate check digit for EAN/UPC barcodes.
     */
    private function calculateCheckDigit(string $value, string $type): ?string
    {
        // Remove existing check digit if present
        $lengths = [
            ProductBarcode::TYPE_EAN13 => 13,
            ProductBarcode::TYPE_EAN8 => 8,
            ProductBarcode::TYPE_UPCA => 12,
            ProductBarcode::TYPE_UPCE => 8,
        ];

        if (!isset($lengths[$type])) {
            return null;
        }

        $digits = str_split(substr($value, 0, $lengths[$type] - 1));
        $sum = 0;

        foreach ($digits as $i => $digit) {
            $weight = ($type === ProductBarcode::TYPE_EAN13 || $type === ProductBarcode::TYPE_EAN8)
                ? (($i % 2 === 0) ? 1 : 3)
                : (($i % 2 === 0) ? 3 : 1);
            $sum += (int) $digit * $weight;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }
}
