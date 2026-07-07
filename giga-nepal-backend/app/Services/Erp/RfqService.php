<?php

namespace App\Services\Erp;

use App\Models\Erp\RfqRequest;
use Illuminate\Support\Facades\DB;

/**
 * Request-for-quote intake. A customer (or guest, via admin) submits desired
 * items; the sales team responds with a Quotation (QuotationService).
 */
class RfqService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    /**
     * @param array{user_id?:int, company_name?:string, contact_name?:string, contact_email?:string,
     *   contact_phone?:string, marketplace_id?:int, currency?:string, notes?:string,
     *   items:array<int, array{name:string, sku?:string, product_id?:int, quantity:float,
     *   target_price?:float, notes?:string}>} $data
     */
    public function create(array $data): RfqRequest
    {
        return DB::transaction(function () use ($data) {
            $rfq = RfqRequest::create([
                'rfq_number' => $this->numbers->next('RFQ', 'RFQ-'),
                'user_id' => $data['user_id'] ?? null,
                'company_name' => $data['company_name'] ?? null,
                'contact_name' => $data['contact_name'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'marketplace_id' => $data['marketplace_id'] ?? null,
                'currency' => $data['currency'] ?? 'USD',
                'status' => 'open',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $line) {
                $rfq->items()->create([
                    'product_id' => $line['product_id'] ?? null,
                    'sku' => $line['sku'] ?? null,
                    'name' => $line['name'],
                    'quantity' => (float) $line['quantity'],
                    'target_price' => $line['target_price'] ?? null,
                    'notes' => $line['notes'] ?? null,
                ]);
            }

            return $rfq->fresh('items');
        });
    }
}
