<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\SellerMarketOpportunity;
use Illuminate\Support\Facades\DB;

class SellerIntelligenceService
{
    /**
     * Get trending MPNs for seller opportunities.
     */
    public function getTrendingMpns(int $limit = 20, ?string $marketplaceId = null): array
    {
        $q = SellerMarketOpportunity::active()->topOpportunities($limit);

        if ($marketplaceId) {
            $q->ofMarketplace($marketplaceId);
        }

        return $q->get()->map(fn ($opp) => [
            'mpn' => $opp->mpn,
            'product_name' => $opp->product_name,
            'brand' => $opp->brand,
            'category' => $opp->category,
            'demand_score' => $opp->demand_score,
            'search_volume' => $opp->search_volume,
            'search_growth' => $opp->search_growth,
            'order_count' => $opp->order_count,
            'rfq_count' => $opp->rfq_count,
            'bom_occurrence' => $opp->bom_occurrence,
            'current_supply' => $opp->current_supply,
            'opportunity_reason' => $opp->opportunity_reason,
            'regional_demand' => $opp->regional_demand,
        ])->toArray();
    }

    /**
     * Get fast-selling categories.
     */
    public function getFastSellingCategories(int $limit = 10, ?string $marketplaceId = null): array
    {
        return SellerMarketOpportunity::active()
            ->select('category', DB::raw('SUM(demand_score) as total_demand'), DB::raw('COUNT(*) as product_count'))
            ->groupBy('category')
            ->orderByDesc('total_demand')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get unfulfilled demand (searched but no stock).
     */
    public function getUnfulfilledDemand(int $limit = 20): array
    {
        return SellerMarketOpportunity::active()
            ->where('current_supply', 0)
            ->orWhere('demand_score', '>', 50)
            ->orderByDesc('demand_score')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get high-demand low-supply products.
     */
    public function getSupplyGaps(int $limit = 20): array
    {
        return SellerMarketOpportunity::active()
            ->whereColumn('demand_score', '>', 'current_supply')
            ->orderByDesc(DB::raw('demand_score - current_supply'))
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get regional demand summary.
     */
    public function getRegionalDemand(?string $marketplaceId = null): array
    {
        $q = SellerMarketOpportunity::active();

        if ($marketplaceId) {
            $q->ofMarketplace($marketplaceId);
        }

        return $q->select('category', DB::raw('SUM(search_volume) as total_searches'), DB::raw('SUM(order_count) as total_orders'))
            ->groupBy('category')
            ->orderByDesc('total_searches')
            ->get()
            ->toArray();
    }

    /**
     * Build seller opportunity insight.
     */
    public function getOpportunityInsight(string $mpn): array
    {
        $opp = SellerMarketOpportunity::where('mpn', $mpn)->first();

        if (!$opp) {
            return ['mpn' => $mpn, 'found' => false, 'message' => 'No demand data available for this MPN.'];
        }

        $insights = [];

        if ($opp->search_growth > 20) {
            $insights[] = ['type' => 'growing_demand', 'message' => "Search volume growing at {$opp->search_growth}%."];
        }
        if ($opp->rfq_count > 5) {
            $insights[] = ['type' => 'rfq_demand', 'message' => "{$opp->rfq_count} RFQs requesting this MPN."];
        }
        if ($opp->bom_occurrence > 10) {
            $insights[] = ['type' => 'bom_demand', 'message' => "Appears in {$opp->bom_occurrence} project BOMs."];
        }
        if ($opp->current_supply < 3) {
            $insights[] = ['type' => 'low_supply', 'message' => 'Low current supply in marketplace.'];
        }

        return [
            'mpn' => $mpn,
            'found' => true,
            'demand_score' => $opp->demand_score,
            'insights' => $insights,
            'recommended_action' => $opp->opportunity_reason,
        ];
    }

    /**
     * Record a search event for intelligence.
     */
    public function recordSearch(string $query, ?int $userId = null, ?string $sessionId = null, ?string $countryCode = null): void
    {
        if (DB::getSchemaBuilder()->hasTable('product_search_events')) {
            DB::table('product_search_events')->insert([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'search_query' => $query,
                'normalized_query' => strtoupper(preg_replace('/\s+/', '', $query)),
                'search_type' => 'text',
                'country_code' => $countryCode,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Get search analytics for admin.
     */
    public function getSearchAnalytics(int $days = 30, int $limit = 20): array
    {
        if (!DB::getSchemaBuilder()->hasTable('product_search_events')) {
            return [];
        }

        return DB::table('product_search_events')
            ->where('created_at', '>=', now()->subDays($days))
            ->where('search_query', '!=', '')
            ->select('search_query', DB::raw('count(*) as search_count'))
            ->groupBy('search_query')
            ->orderByDesc('search_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
