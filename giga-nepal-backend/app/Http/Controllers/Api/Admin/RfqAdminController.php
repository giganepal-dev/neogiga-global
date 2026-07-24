<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Erp\RfqRequest;
use App\Services\Erp\RfqBiddingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RfqAdminController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly RfqBiddingService $bidding,
    ) {}

    /** GET /api/v1/admin/rfq — list all RFQs */
    public function index(): JsonResponse
    {
        $rfqs = RfqRequest::with(['items', 'assignments.vendor', 'bids.vendor', 'awards'])
            ->latest()
            ->paginate(25);

        return $this->success($rfqs);
    }

    /** GET /api/v1/admin/rfq/{rfq} — show RFQ with bids */
    public function show(int $rfq): JsonResponse
    {
        $record = RfqRequest::with(['items', 'assignments.vendor', 'bids.items.rfqItem', 'bids.vendor', 'awards'])
            ->findOrFail($rfq);

        return $this->success($record);
    }

    /** POST /api/v1/admin/rfq/{rfq}/assign — assign sellers to RFQ */
    public function assign(Request $request, int $rfq): JsonResponse
    {
        $validated = $request->validate([
            'vendor_ids' => 'required|array|min:1',
            'vendor_ids.*' => 'exists:vendors,id',
            'deadline' => 'nullable|date|after:now',
        ]);

        $record = RfqRequest::findOrFail($rfq);
        $assignments = $this->bidding->assignSellers($record, $validated['vendor_ids'], $validated['deadline'] ?? null);

        return $this->success(['assignments' => $assignments]);
    }

    /** GET /api/v1/admin/rfq/{rfq}/comparison — compare all bids */
    public function comparison(int $rfq): JsonResponse
    {
        $record = RfqRequest::findOrFail($rfq);
        $comparison = $this->bidding->comparison($record);

        return $this->success([
            'rfq_id' => $rfq,
            'rfq_title' => $record->title,
            'bids' => $comparison,
        ]);
    }

    /** POST /api/v1/admin/rfq/{rfq}/award — award a bid */
    public function award(Request $request, int $rfq): JsonResponse
    {
        $validated = $request->validate([
            'bid_id' => 'required|exists:rfq_bids,id',
        ]);

        $record = RfqRequest::findOrFail($rfq);
        $award = $this->bidding->award($record, $validated['bid_id'], $request->user()->id);

        return $this->success($award);
    }

    /** POST /api/v1/admin/rfq/{rfq}/convert-to-quotation — convert awarded bid to quotation */
    public function convertToQuotation(int $rfq): JsonResponse
    {
        $record = RfqRequest::findOrFail($rfq);
        $latestAward = $record->awards()->where('status', 'awarded')->first();

        if (! $latestAward) {
            return $this->error('No awarded bid found for this RFQ.', 422);
        }

        $quotation = $this->bidding->convertToQuotation($record, $latestAward->bid_id);

        return $this->success($quotation);
    }
}
