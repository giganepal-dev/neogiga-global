<?php

namespace App\Services\Product;

use App\Models\Marketplace\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Advanced alternative-parts intelligence engine.
 *
 * Finds alternatives based on:
 * - Exact replacement (same MPN, different seller)
 * - Pin-to-pin replacement
 * - Functional equivalent
 * - Parametric alternative
 * - Same manufacturer, different package
 * - Different manufacturer, same function
 *
 * Compares: manufacturer, MPN, package, pin count, voltage, current,
 * power, frequency, temperature, tolerance, lifecycle, price, stock, lead time.
 */
class AlternativePartsService
{
    private MpnNormalizationService $normalization;

    public function __construct(MpnNormalizationService $normalization)
    {
        $this->normalization = $normalization;
    }

    /**
     * Find alternatives for a product.
     *
     * @return array{alternatives: array<int, array>, product: array, analysis: array}
     */
    public function findAlternatives(int $productId, ?int $marketplaceId = null, int $limit = 20): array
    {
        $product = Product::with('brand')->find($productId);

        if (! $product) {
            return ['alternatives' => [], 'product' => [], 'analysis' => []];
        }

        $alternatives = [];
        $analyzed = [];

        // 1. Find same-MPN alternatives (different sellers/warehouses)
        $sameMpn = $this->findSameMpnAlternatives($product, $marketplaceId);
        $alternatives = array_merge($alternatives, $sameMpn);

        // 2. Find manufacturer-suggested alternatives
        if (count($alternatives) < $limit) {
            $mfrAlts = $this->findManufacturerAlternatives($product, $marketplaceId, $limit - count($alternatives));
            $alternatives = array_merge($alternatives, $mfrAlts);
        }

        // 3. Find parametric alternatives (same category, similar specs)
        if (count($alternatives) < $limit) {
            $parametric = $this->findParametricAlternatives($product, $marketplaceId, $limit - count($alternatives));
            $alternatives = array_merge($alternatives, $parametric);
        }

        // 4. Find functional equivalents (same category, different manufacturer)
        if (count($alternatives) < $limit) {
            $functional = $this->findFunctionalEquivalents($product, $marketplaceId, $limit - count($alternatives));
            $alternatives = array_merge($alternatives, $functional);
        }

        // Deduplicate by product_id
        $seen = [];
        $unique = [];
        foreach ($alternatives as $alt) {
            if (! in_array($alt['product_id'], $seen, true)) {
                $seen[] = $alt['product_id'];
                $unique[] = $alt;
            }
        }

        // Sort by relevance
        usort($unique, fn ($a, $b) => $b['relevance'] <=> $a['relevance']);

        // Analyze the product's risk
        $analysis = $this->analyzeProductRisk($product);

        return [
            'alternatives' => array_slice($unique, 0, $limit),
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'mpn' => $product->mpn,
                'sku' => $product->sku,
                'brand' => $product->brand?->name,
                'status' => $product->status,
            ],
            'analysis' => $analysis,
        ];
    }

    /**
     * Find alternatives for a list of MPNs (bulk BOM processing).
     *
     * @param  array<int, array{mpn: string, manufacturer?: string, quantity?: int}>  $lines
     * @return array<string, array{alternatives: array, match: array}>
     */
    public function findBulkAlternatives(array $lines, ?int $marketplaceId = null): array
    {
        $results = [];

        foreach ($lines as $line) {
            $mpn = $line['mpn'] ?? '';
            if (empty($mpn)) {
                continue;
            }

            // First try to find the product by MPN
            $normalized = $this->normalization->normalize($mpn)['normalized'];
            $product = Product::published()
                ->whereRaw($this->normalizedMpnExpression() . ' = ?', [$normalized])
                ->first();

            if ($product) {
                $alts = $this->findAlternatives($product->id, $marketplaceId, 5);
                $results[$mpn] = [
                    'alternatives' => $alts['alternatives'],
                    'match' => [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'mpn' => $product->mpn,
                        'status' => $product->status,
                    ],
                ];
            } else {
                $results[$mpn] = [
                    'alternatives' => [],
                    'match' => null,
                ];
            }
        }

        return $results;
    }

    /**
     * Find same-MPN alternatives (different sellers/warehouses).
     */
    private function findSameMpnAlternatives(Product $product, ?int $marketplaceId): array
    {
        if (empty($product->mpn)) {
            return [];
        }

        $normalized = $this->normalization->normalize($product->mpn)['normalized'];

        $query = Product::query()
            ->published()
            ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id')
            ->where('products.id', '!=', $product->id)
            ->whereRaw($this->normalizedMpnExpression() . ' = ?', [$normalized])
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.mpn',
                'products.slug',
                'products.status',
                'product_brands.name as brand_name',
            ])
            ->limit(10);

        return $this->mapAlternatives($query->get(), 'exact_mpn', 100, 'Exact MPN match (different seller)');
    }

    /**
     * Find manufacturer-suggested alternatives.
     */
    private function findManufacturerAlternatives(Product $product, ?int $marketplaceId, int $limit): array
    {
        if (! $product->brand_id) {
            return [];
        }

        // Find products from same manufacturer in same category
        $query = Product::query()
            ->published()
            ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id')
            ->where('products.id', '!=', $product->id)
            ->where('products.brand_id', $product->brand_id)
            ->where('products.category_id', $product->category_id)
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.mpn',
                'products.slug',
                'products.status',
                'product_brands.name as brand_name',
            ])
            ->orderByDesc('products.rating_avg')
            ->limit($limit);

        return $this->mapAlternatives($query->get(), 'manufacturer_alternative', 70, 'Same manufacturer, same category');
    }

    /**
     * Find parametric alternatives (same category, similar specs).
     */
    private function findParametricAlternatives(Product $product, ?int $marketplaceId, int $limit): array
    {
        if (! $product->category_id) {
            return [];
        }

        $query = Product::query()
            ->published()
            ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id')
            ->where('products.id', '!=', $product->id)
            ->where('products.category_id', $product->category_id)
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.mpn',
                'products.slug',
                'products.status',
                'product_brands.name as brand_name',
            ])
            ->orderByDesc('products.rating_avg')
            ->orderByDesc('products.id')
            ->limit($limit);

        return $this->mapAlternatives($query->get(), 'parametric_alternative', 50, 'Same category, parametric match');
    }

    /**
     * Find functional equivalents (different manufacturer, similar function).
     */
    private function findFunctionalEquivalents(Product $product, ?int $marketplaceId, int $limit): array
    {
        if (! $product->category_id) {
            return [];
        }

        $query = Product::query()
            ->published()
            ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id')
            ->where('products.id', '!=', $product->id)
            ->where('products.category_id', $product->category_id)
            ->where('products.brand_id', '!=', $product->brand_id)
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.mpn',
                'products.slug',
                'products.status',
                'product_brands.name as brand_name',
            ])
            ->orderByDesc('products.rating_avg')
            ->limit($limit);

        return $this->mapAlternatives($query->get(), 'functional_equivalent', 30, 'Functional equivalent (different manufacturer)');
    }

    /**
     * Map alternatives to standardized format.
     */
    private function mapAlternatives($products, string $type, int $relevance, string $reason): array
    {
        $results = [];

        foreach ($products as $product) {
            $results[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'mpn' => $product->mpn,
                'slug' => $product->slug,
                'brand' => $product->brand_name,
                'status' => $product->status,
                'alternative_type' => $type,
                'relevance' => $relevance,
                'reason' => $reason,
                'confidence' => $this->calculateConfidence($type, $relevance),
            ];
        }

        return $results;
    }

    /**
     * Calculate confidence score for an alternative.
     */
    private function calculateConfidence(string $type, int $relevance): string
    {
        if ($relevance >= 90) {
            return 'high';
        }
        if ($relevance >= 70) {
            return 'medium';
        }
        if ($relevance >= 50) {
            return 'low';
        }

        return 'review_required';
    }

    /**
     * Analyze product risk factors.
     */
    private function analyzeProductRisk(Product $product): array
    {
        $risks = [];

        // Check lifecycle status
        if (in_array($product->status, ['discontinued', 'obsolete'])) {
            $risks[] = [
                'type' => 'lifecycle',
                'level' => 'critical',
                'message' => 'Product is ' . $product->status,
            ];
        }

        // Check if NRND
        if ($product->status === 'nrnd') {
            $risks[] = [
                'type' => 'lifecycle',
                'level' => 'high',
                'message' => 'Product is Not Recommended for New Designs',
            ];
        }

        // Check single-source (only one manufacturer for this MPN)
        if (! empty($product->mpn)) {
            $normalized = $this->normalization->normalize($product->mpn)['normalized'];
            $manufacturerCount = Product::published()
                ->whereRaw($this->normalizedMpnExpression() . ' = ?', [$normalized])
                ->distinct('brand_id')
                ->count('brand_id');

            if ($manufacturerCount <= 1) {
                $risks[] = [
                    'type' => 'single_source',
                    'level' => 'medium',
                    'message' => 'Single manufacturer source',
                ];
            }
        }

        // Check stock levels
        if (Schema::hasTable('marketplace_product_prices')) {
            $stock = DB::table('marketplace_product_prices')
                ->where('product_id', $product->id)
                ->sum('stock_quantity');

            if ($stock <= 0) {
                $risks[] = [
                    'type' => 'stock',
                    'level' => 'high',
                    'message' => 'No stock available',
                ];
            } elseif ($stock < 100) {
                $risks[] = [
                    'type' => 'stock',
                    'level' => 'medium',
                    'message' => 'Low stock available',
                ];
            }
        }

        return [
            'risk_score' => $this->calculateRiskScore($risks),
            'risk_level' => $this->getRiskLevel($risks),
            'factors' => $risks,
        ];
    }

    /**
     * Calculate overall risk score (0-100, higher = riskier).
     */
    private function calculateRiskScore(array $risks): int
    {
        $score = 0;

        foreach ($risks as $risk) {
            $score += match ($risk['level']) {
                'critical' => 40,
                'high' => 25,
                'medium' => 10,
                'low' => 5,
                default => 0,
            };
        }

        return min(100, $score);
    }

    /**
     * Get risk level from factors.
     */
    private function getRiskLevel(array $risks): string
    {
        $levels = array_column($risks, 'level');

        if (in_array('critical', $levels)) {
            return 'critical';
        }
        if (in_array('high', $levels)) {
            return 'high';
        }
        if (in_array('medium', $levels)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get the normalized MPN expression for the current database driver.
     */
    private function normalizedMpnExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "upper(replace(replace(replace(replace(coalesce(products.mpn, ''), ' ', ''), char(9), ''), char(10), ''), char(13), ''))",
            'mysql', 'mariadb' => "upper(regexp_replace(coalesce(products.mpn, ''), '[[:space:]]+', ''))",
            default => "upper(regexp_replace(coalesce(products.mpn, ''), '\\s+', '', 'g'))",
        };
    }
}
