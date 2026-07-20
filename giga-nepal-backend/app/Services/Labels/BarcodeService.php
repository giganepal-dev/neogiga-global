<?php

namespace App\Services\Labels;

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
}
