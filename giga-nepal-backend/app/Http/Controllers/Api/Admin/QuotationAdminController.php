<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Erp\Quotation;
use App\Models\Erp\RfqRequest;
use App\Services\Erp\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin B2B sales: review RFQs and issue quotations (admin.token gated).
 * All quotation totals are computed server-side in QuotationService.
 */
class QuotationAdminController extends Controller
{
    public function __construct(private readonly QuotationService $quotations)
    {
    }

    public function rfqs(Request $request): JsonResponse
    {
        $q = RfqRequest::query()->withCount('items');
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        return response()->json(['success' => true, 'data' => $q->orderByDesc('id')->paginate(30)]);
    }

    public function showRfq(RfqRequest $rfq): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $rfq->load(['items', 'quotations'])]);
    }

    public function quotations(Request $request): JsonResponse
    {
        $q = Quotation::query();
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        return response()->json(['success' => true, 'data' => $q->orderByDesc('id')->paginate(30)]);
    }

    public function showQuotation(Quotation $quotation): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $quotation->load(['items', 'rfqRequest'])]);
    }

    public function storeQuotation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rfq_request_id' => ['nullable', 'integer', 'exists:rfq_requests,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'currency' => ['nullable', 'string', 'size:3'],
            'valid_until' => ['nullable', 'date', 'after:today'],
            'shipping_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:190'],
            'items.*.sku' => ['nullable', 'string', 'max:80'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Default the quote recipient to the RFQ's owner when not supplied.
        if (empty($data['user_id']) && !empty($data['rfq_request_id'])) {
            $data['user_id'] = optional(RfqRequest::find($data['rfq_request_id']))->user_id;
        }

        $data['created_by'] = optional($request->user())->id;
        $quote = $this->quotations->create($data);

        return response()->json(['success' => true, 'data' => $quote->load('items')], 201);
    }

    public function sendQuotation(Quotation $quotation): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->quotations->send($quotation)]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function rejectQuotation(Quotation $quotation): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->quotations->reject($quotation)]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
