<?php

namespace App\Services\Erp;

use App\Models\Erp\Quotation;
use App\Models\Erp\RfqRequest;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

/**
 * RFQ bidding lifecycle — assign sellers, collect bids, compare, award, convert to quotation.
 *
 * Privacy: sellers never see competing bids, customer identity, or admin notes.
 * Admin sees full comparison. Award can be full or item-level partial.
 */
class RfqBiddingService
{
    public function __construct(
        private readonly QuotationService $quotations,
    ) {}

    /**
     * Assign one or more sellers to an RFQ.
     */
    public function assignSellers(RfqRequest $rfq, array $vendorIds, ?string $deadline = null): array
    {
        $assignments = [];
        foreach ($vendorIds as $vid) {
            $a = $rfq->assignments()->firstOrCreate(
                ['vendor_id' => $vid],
                [
                    'status' => 'invited',
                    'deadline_at' => $deadline,
                    'invited_at' => now(),
                ],
            );
            $assignments[] = $a;
        }

        return $assignments;
    }

    /**
     * Seller submits a bid with per-item pricing.
     */
    public function submitBid(int $assignmentId, array $data): array
    {
        return DB::transaction(function () use ($assignmentId, $data) {
            $assignment = \App\Models\RfqAssignment::findOrFail($assignmentId);

            // Update assignment status
            $assignment->update(['status' => 'bid_submitted']);

            // Create bid
            $bid = $assignment->rfq->bids()->create([
                'assignment_id' => $assignment->id,
                'vendor_id' => $assignment->vendor_id,
                'status' => 'submitted',
                'cover_note' => $data['cover_note'] ?? null,
                'currency' => $data['currency'] ?? 'USD',
                'lead_time_days' => $data['lead_time_days'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'terms' => $data['terms'] ?? null,
                'submitted_at' => now(),
            ]);

            // Per-item pricing
            $totalBid = 0;
            foreach ($data['items'] as $item) {
                $unitPrice = (float) $item['unit_price'];
                $quantity = (float) ($item['quantity'] ?? $assignment->rfq->items()->find($item['rfq_item_id'])->quantity ?? 1);
                $total = $unitPrice * $quantity;

                $bid->items()->create([
                    'rfq_item_id' => $item['rfq_item_id'],
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'total_price' => $total,
                    'stock_status' => $item['stock_status'] ?? 'available',
                    'substitute_mpn' => $item['substitute_mpn'] ?? null,
                    'item_notes' => $item['item_notes'] ?? null,
                ]);

                $totalBid += $total;
            }

            return ['bid' => $bid, 'total' => $totalBid];
        });
    }

    /**
     * Get all bids for an RFQ (admin view — includes seller names).
     */
    public function comparison(RfqRequest $rfq): array
    {
        return $rfq->load(['assignments.vendor', 'bids.items.rfqItem', 'bids.vendor', 'awards'])
            ->bids
            ->groupBy('vendor_id')
            ->map(fn ($bids) => [
                'vendor' => $bids->first()->vendor?->name ?? 'Unknown',
                'bids' => $bids->map(fn ($b) => [
                    'id' => $b->id,
                    'cover_note' => $b->cover_note,
                    'total' => $b->items->sum('total_price'),
                    'lead_time_days' => $b->lead_time_days,
                    'valid_until' => $b->valid_until,
                    'items' => $b->items->map(fn ($i) => [
                        'rfq_item' => $i->rfqItem?->name,
                        'unit_price' => $i->unit_price,
                        'quantity' => $i->quantity,
                        'total' => $i->total_price,
                        'stock_status' => $i->stock_status,
                    ]),
                    'status' => $b->status,
                    'submitted_at' => $b->submitted_at,
                ]),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Award an RFQ to a winning bid (full award).
     */
    public function award(RfqRequest $rfq, int $bidId, int $awardedByUserId): array
    {
        return DB::transaction(function () use ($rfq, $bidId, $awardedByUserId) {
            $bid = $rfq->bids()->findOrFail($bidId);

            $award = $rfq->awards()->create([
                'bid_id' => $bid->id,
                'awarded_by' => $awardedByUserId,
                'status' => 'awarded',
                'awarded_at' => now(),
            ]);

            $bid->update(['status' => 'awarded']);
            $rfq->update(['status' => 'awarded']);

            // Mark losing bids as rejected
            $rfq->bids()->where('id', '!=', $bidId)->update(['status' => 'rejected']);

            return $award;
        });
    }

    /**
     * Convert an awarded bid into a quotation for the customer.
     */
    public function convertToQuotation(RfqRequest $rfq, int $bidId): Quotation
    {
        $bid = $rfq->bids()->findOrFail($bidId);
        $award = $rfq->awards()->where('bid_id', $bidId)->firstOrFail();

        $quotation = $this->quotations->createFromRfq($rfq, $bid);

        $award->update(['status' => 'converted']);
        $rfq->update(['status' => 'converted']);

        return $quotation;
    }
}
