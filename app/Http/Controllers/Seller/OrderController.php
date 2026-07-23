<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SellerOffer;
use App\Models\SellerShipment;
use App\Models\SellerInventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:vendor');
    }

    public function index(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $query = Order::whereHas('items', function($q) use ($vendor) {
            $q->whereHas('offer', function($q2) use ($vendor) {
                $q2->where('seller_id', $vendor->id);
            });
        });

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->with(['items.offer.product', 'customer', 'shippingAddress'])
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => (clone $query)->count(),
            'new' => (clone $query)->where('status', 'pending')->count(),
            'processing' => (clone $query)->where('status', 'processing')->count(),
            'shipped' => (clone $query)->where('status', 'shipped')->count(),
            'delivered' => (clone $query)->where('status', 'delivered')->count(),
        ];

        return view('seller.orders.index', compact('orders', 'stats'));
    }

    public function show($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $order = Order::whereHas('items', function($q) use ($vendor) {
            $q->whereHas('offer', function($q2) use ($vendor) {
                $q2->where('seller_id', $vendor->id);
            });
        })->findOrFail($id);

        $order->load(['items.offer.product', 'items.offer.warehouse', 'customer', 'shippingAddress']);

        // Get seller-specific items
        $sellerItems = $order->items->filter(function($item) use ($vendor) {
            return $item->offer && $item->offer->seller_id === $vendor->id;
        });

        $shipment = SellerShipment::where('order_id', $id)->first();

        return view('seller.orders.show', compact('order', 'sellerItems', 'shipment'));
    }

    public function confirm($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $order = Order::whereHas('items', function($q) use ($vendor) {
            $q->whereHas('offer', function($q2) use ($vendor) {
                $q2->where('seller_id', $vendor->id);
            });
        })->findOrFail($id);

        if ($order->status !== 'pending') {
            return back()->withErrors(['error' => 'Order cannot be confirmed in current status.']);
        }

        DB::beginTransaction();
        try {
            // Reserve stock for each item
            foreach ($order->items as $item) {
                if (!$item->offer || $item->offer->seller_id !== $vendor->id) {
                    continue;
                }

                $offer = $item->offer;
                
                if ($offer->quantity_available < $item->quantity) {
                    throw new \Exception("Insufficient stock for product: " . ($offer->product?->name ?? $offer->mpn));
                }

                // Reduce available quantity and increase reserved
                $offer->decrement('quantity_available', $item->quantity);
                $offer->increment('quantity_reserved', $item->quantity);

                // Record inventory movement
                SellerInventoryMovement::create([
                    'seller_id' => $vendor->id,
                    'product_id' => $offer->product_id,
                    'offer_id' => $offer->id,
                    'warehouse_id' => $offer->warehouse_id,
                    'type' => 'reservation',
                    'quantity_change' => -$item->quantity,
                    'quantity_before' => $offer->quantity_available + $item->quantity,
                    'quantity_after' => $offer->quantity_available,
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'notes' => "Stock reserved for order #{$order->order_number}",
                ]);
            }

            $order->status = 'processing';
            $order->save();

            // Send notification
            event(new \App\Events\SellerOrderConfirmed($order, $vendor->id));

            DB::commit();

            return redirect()->route('seller.orders.show', $order->id)
                ->with('success', 'Order confirmed and stock reserved.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function reject($id, Request $request)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $order = Order::whereHas('items', function($q) use ($vendor) {
            $q->whereHas('offer', function($q2) use ($vendor) {
                $q2->where('seller_id', $vendor->id);
            });
        })->findOrFail($id);

        if ($order->status !== 'pending') {
            return back()->withErrors(['error' => 'Order cannot be rejected in current status.']);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $order->status = 'cancelled';
            $order->cancellation_reason = $validated['rejection_reason'];
            $order->cancelled_by = 'seller';
            $order->save();

            event(new \App\Events\SellerOrderRejected($order, $vendor->id, $validated['rejection_reason']));

            DB::commit();

            return redirect()->route('seller.orders.index')
                ->with('success', 'Order rejected.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function ship($id, Request $request)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $order = Order::whereHas('items', function($q) use ($vendor) {
            $q->whereHas('offer', function($q2) use ($vendor) {
                $q2->where('seller_id', $vendor->id);
            });
        })->findOrFail($id);

        if (!in_array($order->status, ['processing', 'ready_to_ship'])) {
            return back()->withErrors(['error' => 'Order cannot be shipped in current status.']);
        }

        $validated = $request->validate([
            'carrier' => 'required|string|max:255',
            'tracking_number' => 'required|string|max:255',
            'shipping_cost' => 'nullable|numeric|min:0',
            'package_weight' => 'nullable|numeric|min:0',
            'package_length' => 'nullable|numeric|min:0',
            'package_width' => 'nullable|numeric|min:0',
            'package_height' => 'nullable|numeric|min:0',
            'commercial_invoice' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        DB::beginTransaction();
        try {
            // Create shipment
            $shipment = SellerShipment::create([
                'seller_id' => $vendor->id,
                'order_id' => $order->id,
                'warehouse_id' => $order->items->first()?->offer?->warehouse_id,
                'carrier' => $validated['carrier'],
                'tracking_number' => $validated['tracking_number'],
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'package_weight' => $validated['package_weight'] ?? null,
                'package_length' => $validated['package_length'] ?? null,
                'package_width' => $validated['package_width'] ?? null,
                'package_height' => $validated['package_height'] ?? null,
                'status' => 'shipped',
                'shipped_at' => now(),
            ]);

            // Handle commercial invoice upload
            if ($request->hasFile('commercial_invoice')) {
                $path = $request->file('commercial_invoice')->store('shipments/' . $shipment->id, 'public');
                $shipment->commercial_invoice_path = $path;
                $shipment->save();
            }

            // Update offer quantities - convert reserved to fulfilled
            foreach ($order->items as $item) {
                if (!$item->offer || $item->offer->seller_id !== $vendor->id) {
                    continue;
                }

                $offer = $item->offer;
                
                // Decrease reserved and total quantity
                $offer->decrement('quantity_reserved', $item->quantity);
                $offer->decrement('quantity_available', $item->quantity);

                // Record inventory movement
                SellerInventoryMovement::create([
                    'seller_id' => $vendor->id,
                    'product_id' => $offer->product_id,
                    'offer_id' => $offer->id,
                    'warehouse_id' => $offer->warehouse_id,
                    'type' => 'fulfillment',
                    'quantity_change' => -$item->quantity,
                    'quantity_before' => $offer->quantity_available + $item->quantity,
                    'quantity_after' => $offer->quantity_available,
                    'reference_type' => 'shipment',
                    'reference_id' => $shipment->id,
                    'notes' => "Stock fulfilled for order #{$order->order_number}",
                ]);
            }

            $order->status = 'shipped';
            $order->shipped_at = now();
            $order->save();

            event(new \App\Events\SellerOrderShipped($order, $shipment, $vendor->id));

            DB::commit();

            return redirect()->route('seller.orders.show', $order->id)
                ->with('success', 'Order shipped successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function returns()
    {
        $vendor = Auth::guard('vendor')->user();
        
        $returns = Order::whereHas('items', function($q) use ($vendor) {
            $q->whereHas('offer', function($q2) use ($vendor) {
                $q2->where('seller_id', $vendor->id);
            });
        })
        ->whereNotNull('return_requested_at')
        ->with(['items.offer.product', 'customer'])
        ->latest()
        ->paginate(20);

        return view('seller.orders.returns', compact('returns'));
    }

    public function cancellations()
    {
        $vendor = Auth::guard('vendor')->user();
        
        $cancellations = Order::whereHas('items', function($q) use ($vendor) {
            $q->whereHas('offer', function($q2) use ($vendor) {
                $q2->where('seller_id', $vendor->id);
            });
        })
        ->where('status', 'cancelled')
        ->with(['items.offer.product', 'customer'])
        ->latest()
        ->paginate(20);

        return view('seller.orders.cancellations', compact('cancellations'));
    }
}
