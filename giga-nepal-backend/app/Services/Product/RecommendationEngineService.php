<?php

namespace App\Services\Product;

use App\Models\Marketplace\Product;
use App\Models\Product\FrequentlyBoughtTogether;
use App\Models\Product\ProductRecommendation;
use Illuminate\Support\Facades\DB;

class RecommendationEngineService
{
    /**
     * Get recommendations for a product.
     *
     * @param string $mode best_match|best_value|premium|lowest_price|most_popular
     */
    public function getRecommendations(int $productId, string $mode = 'best_match', int $limit = 10): array
    {
        $product = Product::find($productId);
        if (!$product) return [];

        $recommendations = ProductRecommendation::active()
            ->where('product_id', $productId)
            ->orderByDesc('score')
            ->with('recommendedProduct')
            ->limit($limit * 2)
            ->get();

        // Apply mode-specific sorting
        $results = match ($mode) {
            'best_value' => $recommendations->sortByDesc(function ($r) {
                return $r->score * ($r->recommendedProduct->price > 0 ? 1 / max($r->recommendedProduct->price, 0.01) : 0);
            }),
            'premium' => $recommendations->sortByDesc(function ($r) {
                return $r->recommendation_type === 'premium_alternative' ? $r->score + 10 : $r->score;
            }),
            'lowest_price' => $recommendations->sortBy(function ($r) {
                return $r->recommendedProduct->price ?? PHP_FLOAT_MAX;
            }),
            'most_popular' => $recommendations->sortByDesc(function ($r) {
                return $r->recommendedProduct->view_count ?? 0;
            }),
            default => $recommendations,
        };

        return $results->take($limit)->map(fn ($r) => [
            'product_id' => $r->recommended_product_id,
            'name' => $r->recommendedProduct->name ?? null,
            'mpn' => $r->recommendedProduct->mpn ?? null,
            'sku' => $r->recommendedProduct->sku ?? null,
            'slug' => $r->recommendedProduct->slug ?? null,
            'price' => $r->recommendedProduct->price ?? null,
            'recommendation_type' => $r->recommendation_type,
            'score' => $r->score,
            'explanation' => $r->explanation,
        ])->toArray();
    }

    /**
     * Get frequently bought together products.
     */
    public function getFrequentlyBoughtTogether(int $productId, int $limit = 6): array
    {
        return FrequentlyBoughtTogether::active()
            ->where('product_id', $productId)
            ->with('companionProduct')
            ->orderByDesc('confidence')
            ->limit($limit)
            ->get()
            ->map(fn ($fbt) => [
                'product_id' => $fbt->companion_product_id,
                'name' => $fbt->companionProduct->name ?? null,
                'mpn' => $fbt->companionProduct->mpn ?? null,
                'sku' => $fbt->companionProduct->sku ?? null,
                'slug' => $fbt->companionProduct->slug ?? null,
                'price' => $fbt->companionProduct->price ?? null,
                'confidence' => $fbt->confidence,
                'co_occurrence' => $fbt->co_occurrence_count,
            ])
            ->toArray();
    }

    /**
     * Get similar products (same category, different brand).
     */
    public function getSimilarProducts(int $productId, int $limit = 10): array
    {
        $product = Product::find($productId);
        if (!$product) return [];

        return Product::where('id', '!=', $productId)
            ->where('category_id', $product->category_id)
            ->where('status', 'approved')
            ->orderByDesc('rating_avg')
            ->limit($limit)
            ->get()
            ->map(fn ($p) => [
                'product_id' => $p->id,
                'name' => $p->name,
                'mpn' => $p->mpn,
                'sku' => $p->sku,
                'slug' => $p->slug,
                'price' => $p->price,
            ])
            ->toArray();
    }

    /**
     * Get alternative products (same function, different manufacturer).
     */
    public function getAlternatives(int $productId, int $limit = 10): array
    {
        return ProductRecommendation::active()
            ->where('product_id', $productId)
            ->whereIn('recommendation_type', ['pin_compatible', 'functional_equivalent', 'parametric_match'])
            ->with('recommendedProduct')
            ->orderByDesc('score')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'product_id' => $r->recommended_product_id,
                'name' => $r->recommendedProduct->name ?? null,
                'mpn' => $r->recommendedProduct->mpn ?? null,
                'compatibility_type' => $r->recommendation_type,
                'explanation' => $r->explanation,
                'score' => $r->score,
            ])
            ->toArray();
    }

    /**
     * Get products required for a project.
     */
    public function getProjectRequiredProducts(array $mpns, int $limit = 20): array
    {
        $normalized = array_map(fn ($mpn) => strtoupper(preg_replace('/\s+/', '', $mpn)), $mpns);

        return Product::whereRaw('upper(replace(coalesce(mpn, ""), " ", "")) IN (?)', [implode(',', $normalized)])
            ->where('status', 'approved')
            ->limit($limit)
            ->get()
            ->map(fn ($p) => [
                'product_id' => $p->id,
                'name' => $p->name,
                'mpn' => $p->mpn,
                'sku' => $p->sku,
                'price' => $p->price,
            ])
            ->toArray();
    }

    /**
     * Record a product view for recommendation training.
     */
    public function recordProductView(int $productId, ?int $userId = null, ?string $sessionId = null): void
    {
        if (DB::getSchemaBuilder()->hasTable('product_search_events')) {
            DB::table('product_search_events')->insert([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'search_query' => '',
                'search_type' => 'view',
                'clicked_product_id' => $productId,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Build recommendation explanations.
     */
    public function explainRecommendation(string $type, array $context = []): string
    {
        return match ($type) {
            'similar' => 'Similar product in the same category.',
            'frequently_bought_together' => 'Frequently purchased together with this product.',
            'pin_compatible' => 'Pin-compatible replacement; verify board compatibility.',
            'functional_equivalent' => 'Functional alternative; specifications may differ.',
            'parametric_match' => 'Parametrically similar; check exact specifications.',
            'premium_alternative' => 'Premium option with better specifications or reliability.',
            'budget_alternative' => 'Lower-cost alternative; may have different package or specs.',
            'project_required' => 'Required component for the selected project.',
            'project_optional' => 'Optional upgrade for the selected project.',
            default => 'Recommended based on product similarity and relevance.',
        };
    }
}
