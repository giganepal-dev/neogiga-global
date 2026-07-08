<?php

namespace App\Services\Payments;

use App\Models\Payments\VendorPayout;
use App\Services\Erp\DocumentNumberService;
use Illuminate\Support\Facades\DB;

/**
 * Vendor payout tracking — entirely separate from customer payments. Payouts
 * are never auto-paid; an admin explicitly marks them paid. The payout amount
 * is the server-side sum of its line items.
 */
class VendorPayoutService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    /**
     * @param array{vendor_id:int, currency?:string, method?:string, period_start?:string,
     *   period_end?:string, notes?:string, created_by?:int,
     *   items:array<int, array{order_id?:int, description?:string, amount:float}>} $data
     */
    public function create(array $data): VendorPayout
    {
        return DB::transaction(function () use ($data) {
            $payout = VendorPayout::create([
                'payout_number' => $this->numbers->next('VP', 'VP-'),
                'vendor_id' => $data['vendor_id'],
                'currency' => $data['currency'] ?? 'USD',
                'amount' => 0,
                'status' => 'pending',
                'method' => $data['method'] ?? null,
                'period_start' => $data['period_start'] ?? null,
                'period_end' => $data['period_end'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $total = 0.0;
            foreach ($data['items'] as $line) {
                $amount = round((float) $line['amount'], 2);
                $payout->items()->create([
                    'order_id' => $line['order_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'amount' => $amount,
                ]);
                $total += $amount;
            }

            $payout->update(['amount' => round($total, 2)]);

            return $payout->fresh('items');
        });
    }

    public function approve(VendorPayout $payout): VendorPayout
    {
        if ($payout->status !== 'pending') {
            throw new \RuntimeException('Only a pending payout can be approved.');
        }
        $payout->update(['status' => 'approved', 'approved_at' => now()]);

        return $payout->fresh();
    }

    public function markPaid(VendorPayout $payout): VendorPayout
    {
        if (in_array($payout->status, ['paid', 'rejected'], true)) {
            throw new \RuntimeException('Payout already finalized.');
        }
        $payout->update(['status' => 'paid', 'paid_at' => now()]);

        return $payout->fresh();
    }
}
