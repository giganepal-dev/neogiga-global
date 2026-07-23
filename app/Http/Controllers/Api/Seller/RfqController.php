<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Services\RfqService;
use App\Models\VendorRfq;
use App\Models\VendorQuotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RfqController extends Controller
{
    protected $rfqService;

    public function __construct(RfqService $rfqService)
    {
        $this->rfqService = $rfqService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get available RFQs for seller to quote
     */
    public function available(Request $request)
    {
        $filters = $request->only(['marketplace', 'mpn', 'category_id']);
        $rfqs = $this->rfqService->getAvailableRfqs(Auth::id(), $filters);

        return response()->json([
            'success' => true,
            'data' => $rfqs,
        ]);
    }

    /**
     * Get seller's quotations
     */
    public function myQuotations(Request $request)
    {
        $filters = $request->only(['status', 'rfq_id']);
        $quotations = $this->rfqService->getSellerQuotations(Auth::id(), $filters);

        return response()->json([
            'success' => true,
            'data' => $quotations,
        ]);
    }

    /**
     * Submit quotation for RFQ
     */
    public function submitQuotation(Request $request, VendorRfq $rfq)
    {
        $validated = $request->validate([
            'unit_price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'quantity' => 'nullable|integer|min:1',
            'moq' => 'nullable|integer|min:1',
            'lead_time_days' => 'nullable|integer|min:0',
            'date_code' => 'nullable|string|max:50',
            'condition' => 'nullable|string|in:new,refurbished,used',
            'packaging' => 'nullable|string|in:original,tape_reel,tray,bulk',
            'payment_terms' => 'nullable|string|max:255',
            'shipping_terms' => 'nullable|string|max:255',
            'validity_days' => 'nullable|integer|min:1|max:365',
            'notes' => 'nullable|string|max:2000',
            'warehouse_id' => 'nullable|exists:vendor_warehouses,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        try {
            $quotation = $this->rfqService->createQuotation(Auth::id(), $rfq->id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Quotation submitted successfully.',
                'data' => $quotation,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get single quotation
     */
    public function showQuotation(VendorQuotation $quotation)
    {
        if ($quotation->seller_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $quotation->load(['rfq.product', 'attachments']),
        ]);
    }

    /**
     * Update draft quotation
     */
    public function updateQuotation(Request $request, VendorQuotation $quotation)
    {
        if ($quotation->seller_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'unit_price' => 'sometimes|required|numeric|min:0',
            'quantity' => 'sometimes|required|integer|min:1',
            'lead_time_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:2000',
            'validity_days' => 'nullable|integer|min:1|max:365',
        ]);

        try {
            $updated = $this->rfqService->updateDraftQuotation($quotation, Auth::id(), $validated);

            return response()->json([
                'success' => true,
                'message' => 'Quotation updated successfully.',
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
     * Submit draft quotation
     */
    public function submitDraftQuotation(VendorQuotation $quotation)
    {
        if ($quotation->seller_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $updated = $this->rfqService->submitQuotation($quotation);

            return response()->json([
                'success' => true,
                'message' => 'Quotation submitted successfully.',
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
     * Revise quotation
     */
    public function reviseQuotation(Request $request, VendorQuotation $quotation)
    {
        if ($quotation->seller_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'unit_price' => 'sometimes|required|numeric|min:0',
            'quantity' => 'sometimes|required|integer|min:1',
            'lead_time_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $revised = $this->rfqService->reviseQuotation($quotation, Auth::id(), $validated);

            return response()->json([
                'success' => true,
                'message' => 'Quotation revised successfully.',
                'data' => $revised,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get quotation statistics
     */
    public function stats(Request $request)
    {
        $period = $request->get('period', '30_days');
        $stats = $this->rfqService->getQuotationStats(Auth::id(), $period);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
