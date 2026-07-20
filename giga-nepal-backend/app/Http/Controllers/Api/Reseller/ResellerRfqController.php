<?php

namespace App\Http\Controllers\Api\Reseller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\ResellerRfqAssignment;
use App\Services\Reseller\ResellerContextService;
use App\Services\Reseller\ResellerRfqBidService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ResellerRfqController extends Controller
{
    use ApiResponses;

    public function index(Request $request, ResellerContextService $context): JsonResponse
    {
        $reseller = $context->abortUnlessReseller($request->user());

        if (! Schema::hasTable('reseller_rfq_assignments')) {
            return $this->error('Reseller RFQ migration is pending.', 503);
        }

        return $this->success(
            ResellerRfqAssignment::query()
                ->where('reseller_id', $reseller->id)
                ->with(['rfq.items'])
                ->latest()
                ->paginate(25)
        );
    }

    public function submitBid(Request $request, int $assignment, ResellerContextService $context, ResellerRfqBidService $bids): JsonResponse
    {
        $reseller = $context->abortUnlessReseller($request->user());
        $record = ResellerRfqAssignment::findOrFail($assignment);

        $data = $request->validate([
            'cover_note' => ['nullable', 'string', 'max:3000'],
            'currency' => ['nullable', 'string', 'size:3'],
            'lead_time_days' => ['nullable', 'integer', 'min:1'],
            'valid_until' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.rfq_item_id' => ['required', 'integer', 'exists:rfq_items,id'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.001'],
            'items.*.stock_status' => ['nullable', 'string', 'max:40'],
            'items.*.substitute_mpn' => ['nullable', 'string', 'max:120'],
            'items.*.item_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->success($bids->submitBid($record, $reseller, $data), 201);
    }
}
