<?php

namespace App\Http\Controllers\Api\Admin\Marketing;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Marketing\CampaignAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsAdminController extends Controller
{
    use ApiResponses;

    public function dashboard(CampaignAnalyticsService $analytics): JsonResponse
    {
        return $this->success(['events' => DB::table('analytics_events')->count(), 'newsletter_subscribers' => DB::table('newsletter_subscribers')->count(), 'abandoned_carts' => DB::table('abandoned_carts')->count(), 'campaigns' => $analytics->campaigns(20), 'newsletters' => $analytics->newsletters(20), 'countries' => $analytics->countryDashboard()]);
    }

    public function trendingProducts(): JsonResponse
    {
        return $this->success(DB::table('trending_products')->orderByDesc('score')->limit(25)->get());
    }

    public function trendingCategories(): JsonResponse
    {
        return $this->success(DB::table('trending_categories')->orderByDesc('score')->limit(25)->get());
    }

    public function topSearches(): JsonResponse
    {
        return $this->success(DB::table('top_search_terms')->orderByDesc('search_count')->limit(25)->get());
    }

    public function regionalOrders(): JsonResponse
    {
        return $this->success(DB::table('regional_sales_reports')->orderByDesc('id')->limit(50)->get());
    }

    public function countrySales(): JsonResponse
    {
        return $this->regionalOrders();
    }

    public function campaignPerformance(CampaignAnalyticsService $analytics): JsonResponse
    {
        return $this->success($analytics->campaigns());
    }

    public function newsletterPerformance(CampaignAnalyticsService $analytics): JsonResponse
    {
        return $this->success($analytics->newsletters());
    }
}
