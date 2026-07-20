<?php

namespace App\Services\Reseller;

use App\Models\Reseller;
use App\Models\ResellerRfqAssignment;
use App\Models\ResellerRfqBid;
use Illuminate\Support\Facades\DB;

class ResellerRfqBidService
{
    public function submitBid(ResellerRfqAssignment $assignment, Reseller $reseller, array $data): ResellerRfqBid
    {
        abort_if($assignment->reseller_id !== $reseller->id, 403);

        return DB::transaction(function () use ($assignment, $reseller, $data) {
            $assignment->forceFill(['status' => 'bid_submitted'])->save();

            $bid = ResellerRfqBid::create([
                'rfq_id' => $assignment->rfq_id,
                'assignment_id' => $assignment->id,
                'reseller_id' => $reseller->id,
                'status' => 'submitted',
                'cover_note' => $data['cover_note'] ?? null,
                'currency' => $data['currency'] ?? 'USD',
                'lead_time_days' => $data['lead_time_days'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'submitted_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $qty = (float) ($item['quantity'] ?? 1);
                $unit = (float) $item['unit_price'];
                $bid->items()->create([
                    'rfq_item_id' => $item['rfq_item_id'],
                    'unit_price' => $unit,
                    'quantity' => $qty,
                    'total_price' => round($unit * $qty, 4),
                    'stock_status' => $item['stock_status'] ?? 'available',
                    'substitute_mpn' => $item['substitute_mpn'] ?? null,
                    'item_notes' => $item['item_notes'] ?? null,
                ]);
            }

            return $bid->load('items');
        });
    }
}
