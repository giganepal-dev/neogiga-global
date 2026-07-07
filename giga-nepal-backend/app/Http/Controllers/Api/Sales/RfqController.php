<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Erp\Quotation;
use App\Models\Erp\RfqRequest;
use App\Services\Erp\QuotationService;
use App\Services\Erp\RfqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer B2B request-for-quote (auth: api.token). Customers submit RFQs and
 * accept quotations addressed to them. Ownership is enforced on every read.
 */
class RfqController extends Controller
{
    public function __construct(
        private readonly RfqService $rfqs,
        private readonly QuotationService $quotations,
    ) {
    }

    /** POST /api/v1/rfq */
    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:160'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'marketplace_id' => ['nullable', 'integer'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.name' => ['required', 'string', 'max:190'],
            'items.*.sku' => ['nullable', 'string', 'max:80'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.target_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $data['user_id'] = $request->user()->id;
        $rfq = $this->rfqs->create($data);

        return response()->json(['success' => true, 'data' => $rfq->load('items')], 201);
    }

    /** GET /api/v1/rfq */
    public function index(Request $request): JsonResponse
    {
        $rfqs = RfqRequest::where('user_id', $request->user()->id)
            ->withCount('items')->orderByDesc('id')->paginate(20);

        return response()->json(['success' => true, 'data' => $rfqs]);
    }

    /** GET /api/v1/quotations */
    public function quotes(Request $request): JsonResponse
    {
        $quotes = Quotation::where('user_id', $request->user()->id)
            ->whereIn('status', ['sent', 'accepted', 'rejected', 'expired'])
            ->orderByDesc('id')->paginate(20);

        return response()->json(['success' => true, 'data' => $quotes]);
    }

    /** GET /api/v1/quotations/{quotation} */
    public function showQuote(Request $request, Quotation $quotation): JsonResponse
    {
        if ((int) $quotation->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $quotation->load('items')]);
    }

    /** POST /api/v1/quotations/{quotation}/accept */
    public function acceptQuote(Request $request, Quotation $quotation): JsonResponse
    {
        if ((int) $quotation->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        try {
            return response()->json(['success' => true, 'data' => $this->quotations->accept($quotation)]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
