<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\SellerMarketOpportunity;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\Vendor;
use App\Services\Marketplace\SellerIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellerIntelligenceController extends Controller
{
    public function __construct(
        private SellerIntelligenceService $intelligence,
    ) {}

    /** GET /seller/intelligence */
    public function index(Request $request)
    {
        $vendor = $request->user()->vendor;
        $vendorId = $vendor?->id;

        $stats = [
            'my_products' => $vendorId ? Product::where('vendor_id', $vendorId)->count() : 0,
            'my_active' => $vendorId ? Product::where('vendor_id', $vendorId)->where('status', 'approved')->count() : 0,
            'my_views' => $vendorId ? Product::where('vendor_id', $vendorId)->sum('view_count') : 0,
            'my_sales' => $this->getMySalesCount($vendorId),
            'trending_count' => SellerMarketOpportunity::active()->count(),
            'high_demand' => SellerMarketOpportunity::active()->where('demand_score', '>', 70)->count(),
            'unmet_count' => SellerMarketOpportunity::active()->where('current_supply', 0)->count(),
        ];

        $trending = SellerMarketOpportunity::active()->topOpportunities(5)->get();
        $fastSelling = $this->intelligence->getFastSellingCategories(5);
        $unmet = $this->intelligence->getUnfulfilledDemand(5);

        return view('seller.intelligence.index', compact('stats', 'trending', 'fastSelling', 'unmet', 'vendor'));
    }

    /** GET /seller/intelligence/trending */
    public function trending(Request $request)
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

        $opportunities = $query->orderByDesc('demand_score')->paginate(25)->withQueryString();

        return view('seller.intelligence.trending', compact('opportunities'));
    }

    /** GET /seller/intelligence/categories */
    public function categories(Request $request)
    {
        $categories = $this->intelligence->getFastSellingCategories(50);

        return view('seller.intelligence.categories', compact('categories'));
    }

    /** GET /seller/intelligence/unmet */
    public function unmet(Request $request)
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

        return view('seller.intelligence.unmet', compact('opportunities'));
    }

    /** GET /seller/intelligence/my-products */
    public function myProducts(Request $request)
    {
        $vendor = $request->user()->vendor;
        $vendorId = $vendor?->id;

        if (!$vendorId) {
            return view('seller.intelligence.my-products', ['products' => collect([]), 'vendor' => null]);
        }

        $query = Product::where('vendor_id', $vendorId);

        if ($request->has('q')) {
            $q = $request->input('q');
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('mpn', 'like', "%{$q}%")
                  ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        $products = $query->orderByDesc('view_count')->paginate(25)->withQueryString();

        return view('seller.intelligence.my-products', compact('products', 'vendor'));
    }

    /** GET /seller/intelligence/opportunity/{mpn} */
    public function opportunityDetail(Request $request, string $mpn)
    {
        $insight = $this->intelligence->getOpportunityInsight($mpn);
        $history = SellerMarketOpportunity::where('mpn', $mpn)->first();

        return view('seller.intelligence.opportunity-detail', compact('insight', 'history'));
    }

    private function getMySalesCount(?int $vendorId): int
    {
        if (!$vendorId) return 0;

        if (DB::getSchemaBuilder()->hasTable('order_items')) {
            return (int) DB::table('order_items')
                ->where('vendor_id', $vendorId)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
        }

        return 0;
    }
}
