<?php

namespace App\Services\Erp;

use App\Models\Erp\Quotation;
use App\Models\Erp\RfqRequest;
use Illuminate\Support\Facades\DB;

/**
 * Quotation lifecycle: draft -> sent -> accepted/rejected/expired. All line and
 * document totals are computed server-side from unit_price x quantity (+ tax).
 */
class QuotationService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    /**
     * @param array{rfq_request_id?:int, user_id?:int, currency?:string, valid_until?:string,
     *   shipping_total?:float, notes?:string, created_by?:int,
     *   items:array<int, array{name:string, sku?:string, product_id?:int, quantity:float,
     *   unit_price:float, tax_amount?:float}>} $data
     */
    public function create(array $data): Quotation
    {
        return DB::transaction(function () use ($data) {
            $quote = Quotation::create([
                'quote_number' => $this->numbers->next('QUO', 'QUO-'),
                'rfq_request_id' => $data['rfq_request_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'currency' => $data['currency'] ?? 'USD',
                'status' => 'draft',
                'shipping_total' => round((float) ($data['shipping_total'] ?? 0), 2),
                'valid_until' => $data['valid_until'] ?? null,
                'created_by' => $data['created_by'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $line) {
                $qty = (float) $line['quantity'];
                $price = (float) $line['unit_price'];
                $tax = round((float) ($line['tax_amount'] ?? 0), 2);

                $quote->items()->create([
                    'product_id' => $line['product_id'] ?? null,
                    'sku' => $line['sku'] ?? null,
                    'name' => $line['name'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'tax_amount' => $tax,
                    'line_total' => round($qty * $price + $tax, 2),
                ]);
            }

            return $this->recomputeTotals($quote);
        });
    }

    public function send(Quotation $quote): Quotation
    {
        if (!in_array($quote->status, ['draft'], true)) {
            throw new \RuntimeException('Only a draft quotation can be sent.');
        }

        return DB::transaction(function () use ($quote) {
            $quote->update(['status' => 'sent', 'sent_at' => now()]);
            if ($quote->rfq_request_id) {
                RfqRequest::where('id', $quote->rfq_request_id)->where('status', 'open')->update(['status' => 'quoted']);
            }

            return $quote->fresh('items');
        });
    }

    public function accept(Quotation $quote): Quotation
    {
        if ($quote->status !== 'sent') {
            throw new \RuntimeException('Only a sent quotation can be accepted.');
        }
        if ($quote->isExpired()) {
            $quote->update(['status' => 'expired']);
            throw new \RuntimeException('Quotation has expired.');
        }

        return DB::transaction(function () use ($quote) {
            $quote->update(['status' => 'accepted', 'accepted_at' => now()]);
            if ($quote->rfq_request_id) {
                RfqRequest::where('id', $quote->rfq_request_id)->update(['status' => 'accepted']);
            }

            return $quote->fresh('items');
        });
    }

    public function reject(Quotation $quote): Quotation
    {
        if (!in_array($quote->status, ['sent', 'draft'], true)) {
            throw new \RuntimeException('Cannot reject this quotation.');
        }

        $quote->update(['status' => 'rejected']);

        return $quote->fresh('items');
    }

    private function recomputeTotals(Quotation $quote): Quotation
    {
        $quote->load('items');
        $subtotal = round((float) $quote->items->sum(fn ($i) => (float) $i->quantity * (float) $i->unit_price), 2);
        $tax = round((float) $quote->items->sum('tax_amount'), 2);

        $quote->update([
            'subtotal' => $subtotal,
            'tax_total' => $tax,
            'grand_total' => round($subtotal + $tax + (float) $quote->shipping_total, 2),
        ]);

        return $quote->fresh('items');
    }
}
