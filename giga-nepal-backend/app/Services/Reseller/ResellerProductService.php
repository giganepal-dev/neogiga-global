<?php

namespace App\Services\Reseller;

use App\Models\Marketplace\Product;
use App\Models\Reseller;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ResellerProductService
{
    public function matchByMpn(string $mpn): ?Product
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $mpn) ?? '');

        return Product::query()
            ->where(function ($query) use ($mpn, $normalized) {
                $query->where('mpn', $mpn)
                    ->orWhere('sku', $mpn)
                    ->orWhere('normalized_mpn', $normalized);
            })
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createListing(Reseller $reseller, array $data): Product
    {
        $matched = ! empty($data['mpn']) ? $this->matchByMpn((string) $data['mpn']) : null;

        if ($matched && ! empty($data['link_existing'])) {
            $matched->forceFill(['reseller_id' => $reseller->id])->save();

            return $matched->fresh();
        }

        $slugBase = Str::slug($data['name'] ?? $data['mpn'] ?? 'reseller-product');
        $slug = $slugBase;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$i++;
        }

        return Product::create([
            'name' => $data['name'],
            'slug' => $slug,
            'sku' => $data['sku'] ?? ('RSL-'.$reseller->id.'-'.Str::upper(Str::random(6))),
            'mpn' => $data['mpn'] ?? null,
            'normalized_mpn' => isset($data['mpn']) ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $data['mpn']) ?? '') : null,
            'type' => 'simple',
            'status' => 'draft',
            'reseller_id' => $reseller->id,
            'base_price' => $data['base_price'] ?? 0,
            'sale_price' => $data['sale_price'] ?? null,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'track_inventory' => true,
            'metadata' => [
                'source' => 'reseller_listing',
                'reseller_id' => $reseller->id,
                'matched_catalog_id' => $matched?->id,
            ],
        ]);
    }

    /**
     * @return array{created:int, linked:int, failed:array<int, string>}
     */
    public function importCsv(Reseller $reseller, UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ['created' => 0, 'linked' => 0, 'failed' => ['Could not read CSV file.']];
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), fgetcsv($handle) ?: []);
        $created = 0;
        $linked = 0;
        $failed = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row) ?: [];
            $mpn = trim((string) ($data['mpn'] ?? $data['sku'] ?? ''));
            $name = trim((string) ($data['name'] ?? $mpn));

            if ($name === '') {
                $failed[] = 'Row missing name/mpn';

                continue;
            }

            try {
                $match = $mpn !== '' ? $this->matchByMpn($mpn) : null;
                if ($match) {
                    $match->forceFill(['reseller_id' => $reseller->id])->save();
                    $linked++;
                } else {
                    $this->createListing($reseller, [
                        'name' => $name,
                        'mpn' => $mpn ?: null,
                        'sku' => $data['sku'] ?? null,
                        'base_price' => (float) ($data['price'] ?? $data['base_price'] ?? 0),
                        'stock_quantity' => (int) ($data['quantity'] ?? $data['stock'] ?? 0),
                    ]);
                    $created++;
                }
            } catch (\Throwable $e) {
                $failed[] = $name.': '.$e->getMessage();
            }
        }

        fclose($handle);

        return compact('created', 'linked', 'failed');
    }
}
