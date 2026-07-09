<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display orders list with filters.
     */
    public function index(Request $request)
    {
        $query = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->leftJoin('sellers', 'orders.seller_id', '=', 'sellers.id')
            ->select(
                'orders.*',
                'users.name as customer_name',
                'users.email as customer_email',
                'users.phone as customer_phone',
                'sellers.name as seller_name',
                DB::raw('(SELECT COUNT(*) FROM order_details WHERE order_details.order_id = orders.id) as products_count')
            );

        // Apply filters
        if ($request->filled('payment_status')) {
            $query->where('orders.payment_status', $request->payment_status);
        }

        if ($request->filled('delivery_status')) {
            $query->where('orders.delivery_status', $request->delivery_status);
        }

        if ($request->filled('country')) {
            $query->where('orders.country_code', $request->country);
        }

        if ($request->filled('seller_id')) {
            $query->where('orders.seller_id', $request->seller_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('orders.order_code', 'like', "%{$search}%")
                  ->orWhere('users.name', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%")
                  ->orWhere('users.phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('orders.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('orders.created_at', '<=', $request->date_to);
        }

        $orders = $query->orderBy('orders.created_at', 'desc')->paginate(20);

        // Get filter options
        $countries = DB::table('orders')->distinct()->pluck('country_code');
        $sellers = DB::table('sellers')->where('verification_status', 'approved')->get();

        return view('admin.orders.index', compact('orders', 'countries', 'sellers'));
    }

    /**
     * Display order details.
     */
    public function show($id)
    {
        $order = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->leftJoin('sellers', 'orders.seller_id', '=', 'sellers.id')
            ->where('orders.id', $id)
            ->select(
                'orders.*',
                'users.name as customer_name',
                'users.email as customer_email',
                'users.phone as customer_phone',
                'sellers.name as seller_name'
            )
            ->first();

        if (!$order) {
            abort(404);
        }

        $orderProducts = DB::table('order_details')
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->where('order_details.order_id', $id)
            ->select('order_details.*', 'products.name as product_name', 'products.thumbnail_img')
            ->get();

        $orderUpdates = DB::table('order_updates')
            ->where('order_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.orders.show', compact('order', 'orderProducts', 'orderUpdates'));
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'delivery_status' => 'nullable|in:order_placed,confirmed,processing,shipped,out_for_delivery,delivered,cancelled',
            'payment_status' => 'nullable|in:pending,paid,failed,refunded',
            'tracking_code' => 'nullable|string|max:255',
            'courier_name' => 'nullable|string|max:255',
            'tracking_url' => 'nullable|url|max:255',
        ]);

        $updateData = [];
        if ($request->filled('delivery_status')) {
            $updateData['delivery_status'] = $request->delivery_status;
        }
        if ($request->filled('payment_status')) {
            $updateData['payment_status'] = $request->payment_status;
        }
        if ($request->filled('tracking_code')) {
            $updateData['tracking_code'] = $request->tracking_code;
        }
        if ($request->filled('courier_name')) {
            $updateData['courier_name'] = $request->courier_name;
        }
        if ($request->filled('tracking_url')) {
            $updateData['tracking_url'] = $request->tracking_url;
        }

        if (!empty($updateData)) {
            DB::table('orders')->where('id', $id)->update($updateData);

            // Create order update log
            DB::table('order_updates')->insert([
                'order_id' => $id,
                'status_from' => '',
                'status_to' => $request->delivery_status ?? '',
                'note' => $request->note ?? '',
                'updated_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Order updated successfully');
    }

    /**
     * Cancel order.
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        DB::table('orders')->where('id', $id)->update([
            'delivery_status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // Log cancellation
        DB::table('order_updates')->insert([
            'order_id' => $id,
            'status_from' => '',
            'status_to' => 'cancelled',
            'note' => $request->reason,
            'updated_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.orders.index')->with('success', 'Order cancelled successfully');
    }
}
