<?php

namespace App\Services\Product;

use App\Models\Marketplace\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductCertificationService
{
    /** @return Collection<int, array{label:string,url:?string,source:string}> */
    public function verifiedFor(Product $product): Collection
    {
        $marks = collect($this->metadataMarks($product));

        if (Schema::hasTable('product_resources')
            && Schema::hasColumn('product_resources', 'is_verified')) {
            $query = DB::table('product_resources')
                ->where('product_id', $product->id)
                ->where('type', 'certification')
                ->where('is_verified', true);

            $marks = $marks->concat($query->limit(12)->get()->map(fn ($row) => [
                'label' => $this->label((string) $row->title),
                'url' => data_get($row, 'external_url') ?: (data_get($row, 'file_path') ? url('/storage/'.ltrim((string) data_get($row, 'file_path'), '/')) : null),
                'source' => 'Verified product resource',
            ]));
        }

        if (Schema::hasTable('product_certificates')) {
            $columns = Schema::getColumnListing('product_certificates');
            $query = DB::table('product_certificates')->where('product_id', $product->id);

            if (in_array('certificate_name', $columns, true)
                && in_array('is_verified', $columns, true)) {
                $query->where('is_verified', true);
                if (in_array('expiry_date', $columns, true)) {
                    $query->where(fn ($expiry) => $expiry->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today()));
                }
                $marks = $marks->concat($query->limit(12)->get()->map(fn ($row) => [
                    'label' => $this->label((string) $row->certificate_name),
                    'url' => data_get($row, 'file_path') ? url('/storage/'.ltrim((string) data_get($row, 'file_path'), '/')) : null,
                    'source' => trim((string) data_get($row, 'issuing_authority', 'Verified certificate')),
                ]));
            } elseif (in_array('document_type', $columns, true)
                && in_array('title', $columns, true)
                && in_array('status', $columns, true)) {
                $query->whereIn('document_type', ['certification', 'certificate', 'compliance_doc'])
                    ->whereIn('status', ['verified', 'approved']);
                $marks = $marks->concat($query->limit(12)->get()->map(fn ($row) => [
                    'label' => $this->label((string) $row->title),
                    'url' => data_get($row, 'source_url') ?: (data_get($row, 'file_path') ? url('/storage/'.ltrim((string) data_get($row, 'file_path'), '/')) : null),
                    'source' => 'Approved compliance document',
                ]));
            }
        }

        return $marks
            ->filter(fn ($mark) => trim((string) ($mark['label'] ?? '')) !== '')
            ->unique(fn ($mark) => Str::lower((string) $mark['label']))
            ->take(6)
            ->values();
    }

    /** @return array<int, array{label:string,url:?string,source:string}> */
    private function metadataMarks(Product $product): array
    {
        $values = data_get($product->metadata, 'verified_certifications', []);
        if (! is_array($values)) {
            return [];
        }

        return collect($values)->map(function ($value) {
            $label = is_array($value) ? ($value['name'] ?? $value['label'] ?? '') : $value;
            $url = is_array($value) ? ($value['url'] ?? null) : null;

            return [
                'label' => $this->label((string) $label),
                'url' => is_string($url) && str_starts_with($url, 'https://') ? $url : null,
                'source' => 'Verified catalog metadata',
            ];
        })->all();
    }

    private function label(string $value): string
    {
        $value = trim(strip_tags($value));
        $known = [
            '/\brohs\b/i' => 'RoHS',
            '/\breach\b/i' => 'REACH',
            '/\bfcc\b/i' => 'FCC',
            '/\bce\b/i' => 'CE',
            '/\bul\b/i' => 'UL',
            '/\biso\s*9001\b/i' => 'ISO 9001',
            '/\biso\s*14001\b/i' => 'ISO 14001',
        ];

        foreach ($known as $pattern => $label) {
            if (preg_match($pattern, $value)) {
                return $label;
            }
        }

        return Str::limit($value, 32, '…');
    }
}
