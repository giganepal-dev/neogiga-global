<?php

namespace App\Services\Catalog;

use App\Models\Marketplace\BrandAlias;
use App\Models\Marketplace\ProductBrand;
use Illuminate\Support\Str;

class BrandIdentityResolver
{
    public function normalizeBrandName(?string $name): string
    {
        $name = Str::ascii((string) $name);
        $name = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', ' ', $name)));

        return trim((string) preg_replace('/\s+/', ' ', $name));
    }

    /** @return array{brand: ?ProductBrand, created: bool, matched_by: string} */
    public function resolveOrCreate(?string $name): array
    {
        $normalized = $this->normalizeBrandName($name);
        if ($normalized === '') {
            return ['brand' => null, 'created' => false, 'matched_by' => 'empty'];
        }

        $canonicalName = config('brand_logos.aliases.'.$normalized, $name);
        $canonicalNormalized = $this->normalizeBrandName($canonicalName);
        $brand = ProductBrand::query()
            ->whereRaw('LOWER(slug) = ?', [Str::slug($canonicalName)])
            ->orWhereRaw('LOWER(name) = ?', [strtolower(trim((string) $canonicalName))])
            ->first();
        if ($brand) {
            return ['brand' => $brand, 'created' => false, 'matched_by' => 'canonical_name'];
        }

        $alias = BrandAlias::query()->with('brand')->where('normalized_alias', $normalized)->first();
        if ($alias?->brand) {
            return ['brand' => $alias->brand, 'created' => false, 'matched_by' => 'database_alias'];
        }

        $brand = ProductBrand::query()->get()->first(fn (ProductBrand $candidate) => $this->normalizeBrandName($candidate->name) === $canonicalNormalized);
        if ($brand) {
            return ['brand' => $brand, 'created' => false, 'matched_by' => 'normalized_name'];
        }

        $brand = ProductBrand::create([
            'name' => trim((string) $canonicalName),
            'slug' => Str::slug($canonicalName),
            'logo_status' => 'pending',
            'logo_review_note' => 'Created by importer. Official logo discovery is required before a logo can be published.',
        ]);

        return ['brand' => $brand, 'created' => true, 'matched_by' => 'created_pending'];
    }
}
