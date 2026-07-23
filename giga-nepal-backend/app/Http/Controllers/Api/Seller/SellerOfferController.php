<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Seller\SellerContextService;
use App\Services\Seller\MpnMatchingService;
use App\Models\Marketplace\SellerOffer;
use App\Models\Marketplace\CanonicalProduct;
use App\Models\Marketplace\VendorWarehouse;
use App\Models\Marketplace\Marketplace;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SellerOfferController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly SellerContextService $context,
        private readonly MpnMatchingService $mpnMatching
    ) {
    }

    /**
     * List all offers for the seller
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        $query = SellerOffer::where('seller_id', $vendor->id)
            ->with(['canonicalProduct', 'warehouse', 'marketplace']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }
        if ($request->has('marketplace_id')) {
            $query->where('marketplace_id', $request->marketplace_id);
        }
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->has('search')) {
            $query->whereHas('canonicalProduct', function ($q) use ($request) {
                $q->where('title', 'LIKE', "%{$request->search}%")
                  ->orWhere('mpn', 'LIKE', "%{$request->search}%");
            });
        }

        $offers = $query->latest()->paginate($request->get('per_page', 25));

        return $this->success($offers);
    }

    /**
     * Show a specific offer
     */
    public function show(Request $request, int $offerId): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        $offer = SellerOffer::where('seller_id', $vendor->id)
            ->where('id', $offerId)
            ->with(['canonicalProduct', 'warehouse', 'marketplace'])
            ->firstOrFail();

        return $this->success($offer);
    }

    /**
     * Search MPN to find existing products
     */
    public function searchMpn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mpn' => 'required|string|min:2|max:100',
            'limit' => 'nullable|integer|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        $results = $this->mpnMatching->searchByMpn(
            $request->mpn, 
            $request->get('limit', 20)
        );

        return $this->success([
            'query' => $request->mpn,
            'normalized_query' => $this->mpnMatching->normalizeMpn($request->mpn),
            'results' => $results->map(fn($product) => [
                'id' => $product->id,
                'mpn' => $product->mpn,
                'normalized_mpn' => $product->normalized_mpn,
                'title' => $product->title,
                'brand' => $product->brand?->name,
                'category' => $product->category?->name,
                'image_url' => $product->primaryImage?->url ?? null,
                'has_existing_offer' => $this->mpnMatching->hasExistingOffer($vendor, $product->id),
            ]),
            'total' => $results->count(),
        ]);
    }

    /**
     * Create a new offer for an existing product
     */
    public function store(Request $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        $validator = Validator::make($request->all(), [
            'canonical_product_id' => 'required|integer|exists:canonical_products,id',
            'variation_id' => 'nullable|integer|exists:product_variants,id',
            'warehouse_id' => 'required|integer|exists:vendor_warehouses,id',
            'marketplace_id' => 'required|integer|exists:marketplaces,id',
            'base_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lte:base_price',
            'currency_code' => 'nullable|string|size:3',
            'stock_quantity' => 'nullable|integer|min:0',
            'moq' => 'nullable|integer|min:1',
            'lead_time_days' => 'nullable|integer|min:0',
            'condition_grade' => 'nullable|string|in:new,refurbished,used,factory_surplus',
            'packaging_type' => 'nullable|string|in:original,bulk,repacked',
            'country_of_origin' => 'nullable|string|size:2',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        try {
            $offer = $this->mpnMatching->createOffer($request->user(), $request->all());

            return $this->success($offer->load(['canonicalProduct', 'warehouse', 'marketplace']), 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Update an existing offer
     */
    public function update(Request $request, int $offerId): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        $offer = SellerOffer::where('seller_id', $vendor->id)
            ->where('id', $offerId)
            ->firstOrFail();

        // Prevent editing approved offers without admin review
        if ($offer->approval_status === 'approved' && $request->hasAny(['base_price', 'stock_quantity'])) {
            return $this->error('Approved offers require admin review for price or stock changes. Please submit a revision request.', 422);
        }

        $validator = Validator::make($request->all(), [
            'base_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'moq' => 'nullable|integer|min:1',
            'lead_time_days' => 'nullable|integer|min:0',
            'seller_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        DB::transaction(function () use ($offer, $request) {
            $fillableFields = [
                'base_price', 'sale_price', 'moq', 'order_multiple', 'max_order_qty',
                'lead_time_days', 'fulfillment_type', 'seller_notes', 'allow_backorder',
                'backorder_limit', 'warranty_type', 'warranty_period',
            ];

            foreach ($fillableFields as $field) {
                if ($request->has($field)) {
                    $offer->{$field} = $request->{$field};
                }
            }

            // If stock is being updated, log the movement
            if ($request->has('stock_quantity')) {
                $oldStock = $offer->stock_quantity;
                $newStock = $request->stock_quantity;
                
                if ($newStock !== $oldStock) {
                    $difference = $newStock - $oldStock;
                    $offer->stock_quantity = $newStock;

                    \App\Models\Marketplace\SellerInventoryMovement::create([
                        'seller_offer_id' => $offer->id,
                        'warehouse_id' => $offer->warehouse_id,
                        'movement_type' => $difference > 0 ? 'manual_increase' : 'manual_decrease',
                        'quantity_change' => $difference,
                        'quantity_before' => $oldStock,
                        'quantity_after' => $newStock,
                        'reserved_before' => $offer->reserved_quantity,
                        'reserved_after' => $offer->reserved_quantity,
                        'notes' => 'Manual stock adjustment via API',
                        'created_by' => $request->user()->id,
                    ]);
                }
            }

            $offer->save();
        });

        // Reset approval status if critical fields changed
        if ($offer->approval_status === 'approved' && $request->hasAny(['base_price', 'sale_price'])) {
            $offer->update([
                'approval_status' => 'pending',
                'is_published' => false,
            ]);
        }

        return $this->success($offer->fresh()->load(['canonicalProduct', 'warehouse', 'marketplace']));
    }

    /**
     * Add stock to an offer
     */
    public function addStock(Request $request, int $offerId): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        $offer = SellerOffer::where('seller_id', $vendor->id)
            ->where('id', $offerId)
            ->firstOrFail();

        try {
            $updatedOffer = $this->mpnMatching->addStock(
                $offer, 
                $request->quantity, 
                $request->user(),
                $request->reason ?? 'manual_increase'
            );

            return $this->success($updatedOffer);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Pause/unpause an offer
     */
    public function togglePause(Request $request, int $offerId): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        $offer = SellerOffer::where('seller_id', $vendor->id)
            ->where('id', $offerId)
            ->firstOrFail();

        if ($offer->is_published) {
            $offer->pause($request->reason ?? 'Paused by seller');
            $action = 'paused';
        } else {
            $offer->resume();
            $action = 'resumed';
        }

        return $this->success([
            'message' => "Offer {$action}",
            'offer' => $offer->fresh(),
        ]);
    }

    /**
     * Duplicate offer to another marketplace/warehouse
     */
    public function duplicate(Request $request, int $offerId): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        $validator = Validator::make($request->all(), [
            'marketplace_id' => 'required|integer|exists:marketplaces,id',
            'warehouse_id' => 'required|integer|exists:vendor_warehouses,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        $sourceOffer = SellerOffer::where('seller_id', $vendor->id)
            ->where('id', $offerId)
            ->firstOrFail();

        // Check for existing duplicate
        $existing = SellerOffer::where('seller_id', $vendor->id)
            ->where('canonical_product_id', $sourceOffer->canonical_product_id)
            ->where('marketplace_id', $request->marketplace_id)
            ->where('warehouse_id', $request->warehouse_id)
            ->first();

        if ($existing) {
            return $this->error('An offer already exists for this product in the selected marketplace and warehouse', 422);
        }

        $newOffer = DB::transaction(function () use ($sourceOffer, $request, $vendor) {
            return SellerOffer::create([
                'canonical_product_id' => $sourceOffer->canonical_product_id,
                'variation_id' => $sourceOffer->variation_id,
                'seller_id' => $vendor->id,
                'warehouse_id' => $request->warehouse_id,
                'marketplace_id' => $request->marketplace_id,
                'base_price' => $sourceOffer->base_price,
                'sale_price' => $sourceOffer->sale_price,
                'cost_price' => $sourceOffer->cost_price,
                'currency_code' => $sourceOffer->currency_code,
                'moq' => $sourceOffer->moq,
                'order_multiple' => $sourceOffer->order_multiple,
                'max_order_qty' => $sourceOffer->max_order_qty,
                'stock_quantity' => 0, // Start with zero stock
                'reserved_quantity' => 0,
                'incoming_quantity' => $sourceOffer->incoming_quantity,
                'allow_backorder' => $sourceOffer->allow_backorder,
                'backorder_limit' => $sourceOffer->backorder_limit,
                'lead_time_days' => $sourceOffer->lead_time_days,
                'fulfillment_type' => $sourceOffer->fulfillment_type,
                'status' => 'active',
                'approval_status' => 'pending',
                'is_published' => false,
                'seller_sku' => $sourceOffer->seller_sku ? $sourceOffer->seller_sku . '-COPY' : null,
                'condition_grade' => $sourceOffer->condition_grade,
                'packaging_type' => $sourceOffer->packaging_type,
                'country_of_origin' => $sourceOffer->country_of_origin,
                'warranty_type' => $sourceOffer->warranty_type,
                'warranty_period' => $sourceOffer->warranty_period,
                'created_by' => $request->user()->id,
            ]);
        });

        return $this->success($newOffer->load(['canonicalProduct', 'warehouse', 'marketplace']), 201);
    }

    /**
     * Delete an offer (soft delete)
     */
    public function destroy(Request $request, int $offerId): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        $offer = SellerOffer::where('seller_id', $vendor->id)
            ->where('id', $offerId)
            ->firstOrFail();

        // Cannot delete offers with active orders
        if ($offer->reserved_quantity > 0) {
            return $this->error('Cannot delete offer with reserved stock. Fulfill or release reservations first.', 422);
        }

        $offer->delete();

        return $this->success(['message' => 'Offer deleted successfully']);
    }

    /**
     * Get offer statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        $stats = [
            'total_offers' => SellerOffer::where('seller_id', $vendor->id)->count(),
            'active_offers' => SellerOffer::where('seller_id', $vendor->id)->where('status', 'active')->count(),
            'pending_approval' => SellerOffer::where('seller_id', $vendor->id)->where('approval_status', 'pending')->count(),
            'approved_offers' => SellerOffer::where('seller_id', $vendor->id)->where('approval_status', 'approved')->count(),
            'rejected_offers' => SellerOffer::where('seller_id', $vendor->id)->where('approval_status', 'rejected')->count(),
            'published_offers' => SellerOffer::where('seller_id', $vendor->id)->where('is_published', true)->count(),
            'low_stock_offers' => SellerOffer::where('seller_id', $vendor->id)
                ->whereColumn('stock_quantity', '<=', 'reserved_quantity')
                ->count(),
            'out_of_stock_offers' => SellerOffer::where('seller_id', $vendor->id)
                ->where('stock_quantity', 0)
                ->count(),
        ];

        return $this->success($stats);
    }
}
