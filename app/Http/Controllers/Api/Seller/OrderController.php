<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Models\VendorOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all orders for seller
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'marketplace', 'from_date', 'to_date']);
        $orders = $this->orderService->getOrders(Auth::id(), $filters);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get single order
     */
    public function show(VendorOrder $order)
    {
        $vendorId = Auth::id();

        // Verify seller has items in this order
        $hasSellerItems = $order->items()->whereHas('offer', function ($q) use ($vendorId) {
            $q->where('seller_id', $vendorId);
        })->exists();

        if (!$hasSellerItems) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $order->load(['items.product', 'items.offer.warehouse', 'customer', 'shipments']),
        ]);
    }

    /**
     * Confirm order
     */
    public function confirm(VendorOrder $order)
    {
        try {
            $updated = $this->orderService->confirmOrder($order, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Order confirmed successfully.',
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject order
     */
    public function reject(Request $request, VendorOrder $order)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $updated = $this->orderService->rejectOrder($order, Auth::id(), $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Order rejected.',
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Prepare order for shipment
     */
    public function prepareForShipment(VendorOrder $order)
    {
        try {
            $updated = $this->orderService->prepareForShipment($order, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Order is now being processed.',
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Create shipment for order
     */
    public function createShipment(Request $request, VendorOrder $order)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:vendor_warehouses,id',
            'carrier' => 'required|string|max:100',
            'tracking_number' => 'nullable|string|max:100',
            'service_type' => 'nullable|string|max:100',
            'package_count' => 'nullable|integer|min:1',
            'total_weight' => 'nullable|numeric|min:0',
            'weight_unit' => 'nullable|string|in:kg,lbs',
            'dimensions' => 'nullable|array',
            'shipping_label_url' => 'nullable|string',
            'commercial_invoice_url' => 'nullable|string',
        ]);

        try {
            $shipment = $this->orderService->createShipment($order, Auth::id(), $validated);

            return response()->json([
                'success' => true,
                'message' => 'Shipment created successfully.',
                'data' => $shipment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, VendorOrder $order)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $updated = $this->orderService->cancelOrder($order, Auth::id(), $validated['reason'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully.',
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get order statistics
     */
    public function stats(Request $request)
    {
        $period = $request->get('period', '30_days');
        $stats = $this->orderService->getSellerStats(Auth::id(), $period);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
