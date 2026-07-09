<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(Request $request)
    {
        // Get dashboard metrics
        $metrics = [
            'total_customers' => DB::table('users')->where('role_id', 3)->count(), // Customer role
            'total_products' => DB::table('products')->count(),
            'total_orders' => DB::table('orders')->count(),
            'total_sales' => DB::table('orders')->where('payment_status', 'paid')->sum('grand_total'),
            'total_sellers' => DB::table('sellers')->count(),
            'pending_rfqs' => DB::table('rfqs')->where('status', 'pending')->count(),
            'ai_conversations' => DB::table('ai_conversations')->count(),
            'total_warehouses' => DB::table('warehouses')->count(),
        ];

        // Order statistics by status
        $orderStats = [
            'placed' => DB::table('orders')->where('delivery_status', 'order_placed')->count(),
            'confirmed' => DB::table('orders')->where('delivery_status', 'confirmed')->count(),
            'processing' => DB::table('orders')->where('delivery_status', 'processing')->count(),
            'delivered' => DB::table('orders')->where('delivery_status', 'delivered')->count(),
            'cancelled' => DB::table('orders')->where('delivery_status', 'cancelled')->count(),
            'pending_payment' => DB::table('orders')->where('payment_status', 'pending')->count(),
        ];

        // Recent orders
        $recentOrders = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select(
                'orders.*',
                'users.name as customer_name',
                DB::raw('(SELECT COUNT(*) FROM order_details WHERE order_details.order_id = orders.id) as products_count')
            )
            ->orderBy('orders.created_at', 'desc')
            ->limit(10)
            ->get();

        // Top categories (placeholder - adjust based on actual schema)
        $topCategories = DB::table('categories')
            ->select('categories.*', DB::raw('0 as product_count'), DB::raw('0 as sales'))
            ->limit(5)
            ->get();

        // Queue pending jobs count
        $queuePendingJobs = 0; // Will be implemented with queue monitoring

        // Pending seller applications
        $pendingSellerApps = DB::table('sellers')->where('verification_status', 'pending')->count();

        // Pending orders count for sidebar badge
        $pendingOrdersCount = DB::table('orders')
            ->whereIn('delivery_status', ['order_placed', 'confirmed', 'processing'])
            ->count();

        return view('admin.dashboard.index', compact(
            'metrics',
            'orderStats',
            'recentOrders',
            'topCategories',
            'queuePendingJobs',
            'pendingSellerApps',
            'pendingOrdersCount'
        ));
    }
}
