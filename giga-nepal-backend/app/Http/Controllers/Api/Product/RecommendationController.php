<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Controller;
use App\Services\Product\RecommendationEngineService;
use App\Services\Marketplace\SellerIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function __construct(
        private RecommendationEngineService $recommendations,
        private SellerIntelligenceService $sellerIntelligence,
    ) {}

    /** GET /api/v1/products/{id}/recommendations */
    public function recommendations(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['nullable', 'string', 'in:best_match,best_value,premium,lowest_price,most_popular'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->recommendations->getRecommendations(
                $id,
                $validated['mode'] ?? 'best_match',
                $validated['limit'] ?? 10,
            ),
        ]);
    }

    /** GET /api/v1/products/{id}/frequently-bought-together */
    public function frequentlyBoughtTogether(Request $request, int $id): JsonResponse
    {
        $limit = $request->input('limit', 6);
        return response()->json([
            'success' => true,
            'data' => $this->recommendations->getFrequentlyBoughtTogether($id, $limit),
        ]);
    }

    /** GET /api/v1/products/{id}/similar */
    public function similar(Request $request, int $id): JsonResponse
    {
        $limit = $request->input('limit', 10);
        return response()->json([
            'success' => true,
            'data' => $this->recommendations->getSimilarProducts($id, $limit),
        ]);
    }

    /** GET /api/v1/products/{id}/alternatives */
    public function alternatives(Request $request, int $id): JsonResponse
    {
        $limit = $request->input('limit', 10);
        return response()->json([
            'success' => true,
            'data' => $this->recommendations->getAlternatives($id, $limit),
        ]);
    }

    /** POST /api/v1/products/track-view */
    public function trackView(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $this->recommendations->recordProductView(
            $validated['product_id'],
            $request->user()?->id,
            $request->input('session_id'),
        );

        return response()->json(['success' => true]);
    }

    /** GET /api/v1/seller/intelligence/trending-mpns */
    public function trendingMpns(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        return response()->json([
            'success' => true,
            'data' => $this->sellerIntelligence->getTrendingMpns($limit),
        ]);
    }

    /** GET /api/v1/seller/intelligence/fast-selling */
    public function fastSelling(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->sellerIntelligence->getFastSellingCategories(),
        ]);
    }

    /** GET /api/v1/seller/intelligence/unfulfilled */
    public function unfulfilled(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        return response()->json([
            'success' => true,
            'data' => $this->sellerIntelligence->getUnfulfilledDemand($limit),
        ]);
    }

    /** GET /api/v1/seller/intelligence/opportunity/{mpn} */
    public function opportunity(string $mpn): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->sellerIntelligence->getOpportunityInsight($mpn),
        ]);
    }

    /** POST /api/v1/search/track */
    public function trackSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:500'],
        ]);

        $this->sellerIntelligence->recordSearch(
            $validated['query'],
            $request->user()?->id,
            $request->input('session_id'),
            $request->input('country_code'),
        );

        return response()->json(['success' => true]);
    }
}
