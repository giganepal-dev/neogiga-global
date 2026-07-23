<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\SellerMarketOpportunity;
use App\Services\Marketplace\SellerIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellerIntelligenceAdminController extends Controller
{
    public function __construct(
        private SellerIntelligenceService $intelligence,
    ) {}

    /** GET /admin/seller-intelligence */
    public function dashboard()
    {
        $stats = [
            'total_opportunities' => SellerMarketOpportunity::active()->count(),
            'total_mpns' => SellerMarketOpportunity::active()->distinct('mpn')->count('mpn'),
            'high_demand' => SellerMarketOpportunity::active()->where('demand_score', '>', 70)->count(),
            'unfulfilled' => SellerMarketOpportunity::active()->where('current_supply', 0)->count(),
            'avg_demand' => SellerMarketOpportunity::active()->avg('demand_score'),
            'top_categories' => SellerMarketOpportunity::active()
                ->select('category', DB::raw('SUM(demand_score) as total_demand'), DB::raw('COUNT(*) as count'))
                ->groupBy('category')
                ->orderByDesc('total_demand')
                ->limit(10)
                ->get(),
            'recent_trending' => SellerMarketOpportunity::active()->topOpportunities(10)->get(),
            'unfulfilled_demand' => $this->intelligence->getUnfulfilledDemand(10),
        ];

        return view('admin.seller-intelligence.dashboard', $stats);
    }

    /** GET /admin/seller-intelligence/trending */
    public function trendingMpns(Request $request)
    {
        $query = SellerMarketOpportunity::active();

        if ($request->has('q')) {
            $q = $request->input('q');
            $query->where(function ($w) use ($q) {
                $w->where('mpn', 'like', "%{$q}%")
                  ->orWhere('product_name', 'like', "%{$q}%")
                  ->orWhere('brand', 'like', "%{$q}%");
            });
        }
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        $opportunities = $query->orderByDesc('demand_score')->paginate(25)->withQueryString();
        $categories = SellerMarketOpportunity::active()
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderByDesc('count')
            ->get();

        return view('admin.seller-intelligence.trending', compact('opportunities', 'categories'));
    }

    /** GET /admin/seller-intelligence/unfulfilled */
    public function unfulfilled(Request $request)
    {
        $query = SellerMarketOpportunity::active()
            ->where(function ($q) {
                $q->where('current_supply', 0)
                  ->orWhere('demand_score', '>', 50);
            });

        if ($request->has('q')) {
            $search = $request->input('q');
            $query->where(function ($w) use ($search) {
                $w->where('mpn', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        $opportunities = $query->orderByDesc('demand_score')->paginate(25)->withQueryString();

        return view('admin.seller-intelligence.unfulfilled', compact('opportunities'));
    }

    /** GET /admin/seller-intelligence/supply-gaps */
    public function supplyGaps(Request $request)
    {
        $query = SellerMarketOpportunity::active()
            ->whereColumn('demand_score', '>', 'current_supply');

        if ($request->has('q')) {
            $search = $request->input('q');
            $query->where(function ($w) use ($search) {
                $w->where('mpn', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        $gaps = $query->orderByRaw('demand_score - current_supply DESC')->paginate(25)->withQueryString();

        return view('admin.seller-intelligence.supply-gaps', compact('gaps'));
    }

    /** GET /admin/seller-intelligence/opportunity/{mpn} */
    public function opportunityDetail(string $mpn)
    {
        $insight = $this->intelligence->getOpportunityInsight($mpn);
        $history = SellerMarketOpportunity::where('mpn', $mpn)->first();

        return view('admin.seller-intelligence.opportunity-detail', compact('insight', 'history'));
    }

    /** POST /admin/seller-intelligence/opportunity/{mpn}/create */
    public function createOpportunity(Request $request, string $mpn)
    {
        $validated = $request->validate([
            'product_name' => ['nullable', 'string', 'max:500'],
            'brand' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'demand_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'opportunity_reason' => ['nullable', 'string', 'max:255'],
        ]);

        SellerMarketOpportunity::updateOrCreate(
            ['mpn' => $mpn],
            array_merge($validated, ['is_active' => true])
        );

        return redirect()->back()->with('success', 'Opportunity record created/updated.');
    }

    /** POST /admin/seller-intelligence/opportunity/{mpn}/deactivate */
    public function deactivateOpportunity(string $mpn)
    {
        SellerMarketOpportunity::where('mpn', $mpn)->update(['is_active' => false]);
        return redirect()->back()->with('success', 'Opportunity deactivated.');
    }

    /** POST /admin/seller-intelligence/opportunity/{mpn}/activate */
    public function activateOpportunity(string $mpn)
    {
        SellerMarketOpportunity::where('mpn', $mpn)->update(['is_active' => true]);
        return redirect()->back()->with('success', 'Opportunity activated.');
    }
}
