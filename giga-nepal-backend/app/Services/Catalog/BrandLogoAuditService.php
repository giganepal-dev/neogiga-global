<?php

namespace App\Services\Catalog;

use App\Models\Marketplace\ProductBrand;
use Illuminate\Support\Facades\Storage;

class BrandLogoAuditService
{
    /** @return array{summary: array<string, int>, rows: array<int, array<string, mixed>>} */
    public function audit(bool $discover = false, ?int $limit = null): array
    {
        $query = ProductBrand::query()->withCount('products')->orderBy('id');
        if ($limit) {
            $query->limit($limit);
        }
        $brands = $query->get();
        $discovery = app(BrandLogoDiscoveryService::class);
        $rows = [];
        $summary = ['total_brands' => $brands->count(), 'with_logo' => 0, 'verified' => 0, 'without_logo' => 0, 'broken_local_logo' => 0, 'inactive' => 0, 'duplicate_name_groups' => 0, 'logo_name_mismatch' => 0];
        $names = [];

        foreach ($brands as $brand) {
            $normalized = $discovery->normalizeBrandName($brand->name);
            $names[$normalized] = ($names[$normalized] ?? 0) + 1;
            $hasLogo = filled($brand->logo_path);
            $verified = (bool) ($brand->logo_verified ?? false);
            $broken = $hasLogo && ! str_starts_with((string) $brand->logo_path, 'http')
                && ! Storage::disk((string) config('brand_logos.disk', 'public'))->exists($brand->logo_path);
            $mismatch = $hasLogo && ! $verified && str_contains(strtolower((string) $brand->logo_path), 'supplier');
            $plan = $discover ? $discovery->discoverOfficialLogo($brand) : [
                'official_domain' => $discovery->resolveOfficialDomain($brand), 'proposed_logo_url' => null,
                'source_type' => null, 'confidence' => 0, 'action' => 'not_discovered', 'review_note' => 'Discovery disabled for this audit run.',
            ];
            $rows[] = array_merge([
                'brand_id' => $brand->id, 'brand_name' => $brand->name, 'slug' => $brand->slug,
                'existing_logo' => $brand->logo_path, 'logo_verified' => $verified, 'logo_status' => $brand->logo_status,
                'is_active' => (bool) $brand->is_active, 'products_count' => $brand->products_count,
                'broken_logo' => $broken, 'logo_name_mismatch' => $mismatch,
            ], $plan);
            $summary['with_logo'] += $hasLogo ? 1 : 0;
            $summary['verified'] += $verified ? 1 : 0;
            $summary['without_logo'] += $hasLogo ? 0 : 1;
            $summary['broken_local_logo'] += $broken ? 1 : 0;
            $summary['inactive'] += $brand->is_active ? 0 : 1;
            $summary['logo_name_mismatch'] += $mismatch ? 1 : 0;
        }
        $summary['duplicate_name_groups'] = collect($names)->filter(fn (int $count) => $count > 1)->count();

        return ['summary' => $summary, 'rows' => $rows];
    }
}
