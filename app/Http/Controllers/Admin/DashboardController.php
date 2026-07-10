<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\Order;
use App\Models\Product;
use App\Models\WarehouseInventory;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
        $this->middleware('permission:catalog.import.view');
    }

    /**
     * Display the global commerce dashboard
     */
    public function index(Request $request)
    {
        $marketplace = $request->get('marketplace');
        
        // Marketplace Health Data
        $marketplaces = Marketplace::active()
            ->withCount(['products as active_products' => function ($query) {
                $query->where('status', 'published');
            }])
            ->get()
            ->map(function ($mp) {
                $mp->is_healthy = $mp->active_products > 0 && !$mp->exchangeRates()->stale()->exists();
                $mp->daily_revenue = $this->calculateDailyRevenue($mp);
                return $mp;
            });

        // Global Metrics
        $globalRevenue = $this->calculateGlobalRevenue();
        $activeOrders = Order::whereIn('status', ['pending', 'processing', 'shipped'])->count();
        $pendingShipments = Order::where('status', 'processing')->count();
        $lowStockCount = WarehouseInventory::where('quantity', '<', 10)->count();
        
        // Exchange Rate Status
        $lastRateUpdate = DB::table('exchange_rates')
            ->orderByDesc('created_at')
            ->value('created_at');
        $ratesStale = $lastRateUpdate 
            ? Carbon::parse($lastRateUpdate)->diffInHours(Carbon::now()) > 24
            : true;

        // Recent Orders (limited to active marketplace or global)
        $recentOrdersQuery = Order::with('marketplace')
            ->orderByDesc('created_at')
            ->limit(10);
            
        if ($marketplace) {
            $recentOrdersQuery->where('marketplace_id', $marketplace);
        }
        
        $recentOrders = $recentOrdersQuery->get()->map(function ($order) {
            $order->status_color = $this->getOrderStatusColor($order->status);
            return $order;
        });

        // Category Statistics
        $categoryStats = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('COUNT(*) as count'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'marketplaces' => $marketplaces,
            'globalRevenue' => $globalRevenue,
            'activeOrders' => $activeOrders,
            'pendingShipments' => $pendingShipments,
            'lowStockCount' => $lowStockCount,
            'lastRateUpdate' => $lastRateUpdate ?? Carbon::now(),
            'ratesStale' => $ratesStale,
            'recentOrders' => $recentOrders,
            'categoryLabels' => $categoryStats->pluck('name'),
            'categoryData' => $categoryStats->pluck('count'),
        ]);
    }

    /**
     * Calculate daily revenue for a marketplace
     */
    private function calculateDailyRevenue(Marketplace $marketplace): float
    {
        return Order::where('marketplace_id', $marketplace->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->sum('total');
    }

    /**
     * Calculate global revenue across all marketplaces (in USD)
     */
    private function calculateGlobalRevenue(): float
    {
        $revenue = 0;
        $marketplaces = Marketplace::active()->get();
        
        foreach ($marketplaces as $mp) {
            $mpRevenue = Order::where('marketplace_id', $mp->id)
                ->where('status', 'completed')
                ->where('created_at', '>=', Carbon::now()->subDay())
                ->sum('total');
            
            // Convert to USD using current exchange rate
            $rate = $this->exchangeRateService->getRate($mp->currency, 'USD');
            $revenue += ($mpRevenue / $rate);
        }
        
        return $revenue;
    }

    /**
     * Get color badge for order status
     */
    private function getOrderStatusColor(string $status): string
    {
        return match($status) {
            'pending' => 'warning',
            'processing' => 'info',
            'shipped' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }
}
