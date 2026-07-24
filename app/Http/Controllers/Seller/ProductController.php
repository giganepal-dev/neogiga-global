<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorProfile;
use App\Models\VendorWarehouse;
use App\Models\SellerApplication;
use App\Models\SellerOffer;
use App\Models\Order;
use App\Models\Product;
use App\Services\SellerOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    protected $onboardingService;

    public function __construct(SellerOnboardingService $onboardingService)
    {
        $this->onboardingService = $onboardingService;
        $this->middleware('auth:vendor');
    }

    public function index()
    {
        $vendor = Auth::guard('vendor')->user();
        
        $products = SellerOffer::with(['product', 'warehouse'])
            ->where('seller_id', $vendor->id)
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => SellerOffer::where('seller_id', $vendor->id)->count(),
            'active' => SellerOffer::where('seller_id', $vendor->id)->where('status', 'active')->count(),
            'pending' => SellerOffer::where('seller_id', $vendor->id)->where('status', 'pending_approval')->count(),
            'paused' => SellerOffer::where('seller_id', $vendor->id)->where('status', 'paused')->count(),
        ];

        return view('seller.products.index', compact('products', 'stats'));
    }

    public function create()
    {
        $vendor = Auth::guard('vendor')->user();
        
        // Check if seller is approved
        if (!$this->onboardingService->isSellerApproved($vendor->id)) {
            return redirect()->route('seller.readiness')
                ->with('error', 'Please complete onboarding before adding products.');
        }

        $warehouses = VendorWarehouse::where('vendor_id', $vendor->id)
            ->where('status', 'approved')
            ->get();

        $categories = \App\Models\Category::whereNull('parent_id')->with('children.children')->get();

        return view('seller.products.create', compact('warehouses', 'categories'));
    }

    public function match()
    {
        return view('seller.products.match');
    }

    public function searchMpn(Request $request)
    {
        $request->validate([
            'mpn' => 'required|string|max:255',
        ]);

        $results = $this->onboardingService->searchMpn($request->mpn);

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    public function import()
    {
        return view('seller.products.import');
    }

    public function drafts()
    {
        $vendor = Auth::guard('vendor')->user();
        
        $products = SellerOffer::where('seller_id', $vendor->id)
            ->where('status', 'draft')
            ->latest()
            ->paginate(20);

        return view('seller.products.drafts', compact('products'));
    }

    public function rejected()
    {
        $vendor = Auth::guard('vendor')->user();
        
        $products = SellerOffer::where('seller_id', $vendor->id)
            ->where('status', 'rejected')
            ->latest()
            ->paginate(20);

        return view('seller.products.rejected', compact('products'));
    }

    public function store(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();

        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'mpn' => 'nullable|string|max:255',
            'warehouse_id' => 'required|exists:vendor_warehouses,id',
            'marketplace_id' => 'required|exists:marketplaces,id',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'quantity' => 'required|integer|min:0',
            'moq' => 'nullable|integer|min:1',
            'condition' => 'required|in:new,refurbished,used',
            'date_code' => 'nullable|string|max:50',
            'packaging' => 'nullable|in:original,tape_and_reel,tray,bulk',
            'lead_time_days' => 'nullable|integer|min:0',
            'dispatch_time_days' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            // If no product_id, search by MPN
            if (empty($validated['product_id']) && !empty($validated['mpn'])) {
                $product = Product::whereRaw('LOWER(mpn) = ?', [strtolower($validated['mpn'])])
                    ->orWhereRaw('LOWER(normalized_mpn) = ?', [strtolower($validated['mpn'])])
                    ->first();
                
                if ($product) {
                    $validated['product_id'] = $product->id;
                }
            }

            // Create offer
            $offer = SellerOffer::create([
                'seller_id' => $vendor->id,
                'product_id' => $validated['product_id'] ?? null,
                'mpn' => $validated['mpn'] ?? null,
                'warehouse_id' => $validated['warehouse_id'],
                'marketplace_id' => $validated['marketplace_id'],
                'price' => $validated['price'],
                'currency' => $validated['currency'],
                'quantity_available' => $validated['quantity'],
                'moq' => $validated['moq'] ?? 1,
                'condition' => $validated['condition'],
                'date_code' => $validated['date_code'] ?? null,
                'packaging' => $validated['packaging'] ?? 'original',
                'lead_time_days' => $validated['lead_time_days'] ?? 7,
                'dispatch_time_days' => $validated['dispatch_time_days'] ?? 2,
                'status' => 'pending_approval',
            ]);

            // Create inventory movement for opening stock
            if ($validated['quantity'] > 0) {
                \App\Models\SellerInventoryMovement::create([
                    'seller_id' => $vendor->id,
                    'product_id' => $offer->product_id,
                    'offer_id' => $offer->id,
                    'warehouse_id' => $validated['warehouse_id'],
                    'type' => 'opening_balance',
                    'quantity_change' => $validated['quantity'],
                    'quantity_before' => 0,
                    'quantity_after' => $validated['quantity'],
                    'reference_type' => 'offer_creation',
                    'reference_id' => $offer->id,
                    'notes' => 'Initial stock from product creation',
                ]);
            }

            DB::commit();

            return redirect()->route('seller.products.index')
                ->with('success', 'Product offer submitted for approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $offer = SellerOffer::where('seller_id', $vendor->id)->findOrFail($id);
        
        $warehouses = VendorWarehouse::where('vendor_id', $vendor->id)
            ->where('status', 'approved')
            ->get();

        return view('seller.products.edit', compact('offer', 'warehouses'));
    }

    public function update(Request $request, $id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $offer = SellerOffer::where('seller_id', $vendor->id)->findOrFail($id);

        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'moq' => 'nullable|integer|min:1',
            'lead_time_days' => 'nullable|integer|min:0',
            'dispatch_time_days' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $oldQuantity = $offer->quantity_available;
            $newQuantity = $validated['quantity'];

            $offer->update([
                'price' => $validated['price'],
                'quantity_available' => $newQuantity,
                'moq' => $validated['moq'] ?? $offer->moq,
                'lead_time_days' => $validated['lead_time_days'] ?? $offer->lead_time_days,
                'dispatch_time_days' => $validated['dispatch_time_days'] ?? $offer->dispatch_time_days,
            ]);

            // Record inventory movement if quantity changed
            if ($oldQuantity != $newQuantity) {
                \App\Models\SellerInventoryMovement::create([
                    'seller_id' => $vendor->id,
                    'product_id' => $offer->product_id,
                    'offer_id' => $offer->id,
                    'warehouse_id' => $offer->warehouse_id,
                    'type' => $newQuantity > $oldQuantity ? 'manual_increase' : 'manual_decrease',
                    'quantity_change' => $newQuantity - $oldQuantity,
                    'quantity_before' => $oldQuantity,
                    'quantity_after' => $newQuantity,
                    'reference_type' => 'offer_update',
                    'reference_id' => $offer->id,
                    'notes' => 'Stock updated via product edit',
                ]);
            }

            DB::commit();

            return redirect()->route('seller.products.index')
                ->with('success', 'Product offer updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update product: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $offer = SellerOffer::where('seller_id', $vendor->id)->findOrFail($id);
        
        // Can only delete draft or rejected offers
        if (!in_array($offer->status, ['draft', 'rejected'])) {
            return back()->withErrors(['error' => 'Cannot delete an active or pending offer.']);
        }

        $offer->delete();

        return redirect()->route('seller.products.index')
            ->with('success', 'Product offer deleted.');
    }

    public function pause($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $offer = SellerOffer::where('seller_id', $vendor->id)->findOrFail($id);
        
        if ($offer->status !== 'active') {
            return back()->withErrors(['error' => 'Can only pause active offers.']);
        }

        $offer->pause();

        return back()->with('success', 'Offer paused.');
    }

    public function resume($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $offer = SellerOffer::where('seller_id', $vendor->id)->findOrFail($id);
        
        if ($offer->status !== 'paused') {
            return back()->withErrors(['error' => 'Can only resume paused offers.']);
        }

        $offer->resume();

        return back()->with('success', 'Offer resumed.');
    }

    public function duplicate($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $offer = SellerOffer::where('seller_id', $vendor->id)->findOrFail($id);

        $newOffer = $offer->replicate();
        $newOffer->status = 'draft';
        $newOffer->save();

        return redirect()->route('seller.products.edit', $newOffer->id)
            ->with('success', 'Offer duplicated. Please review and submit.');
    }
}
