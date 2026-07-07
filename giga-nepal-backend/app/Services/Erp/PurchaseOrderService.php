<?php

namespace App\Services\Erp;

use App\Models\Erp\PurchaseOrder;
use App\Models\Erp\Supplier;
use Illuminate\Support\Facades\DB;

/**
 * Purchase order lifecycle. All monetary totals are computed server-side from
 * unit_cost x quantity (+ tax/shipping) — client-supplied totals are ignored.
 * Receiving updates received quantities and PO status; posting received stock
 * into inventory is intentionally NOT coupled here (call the existing
 * inventory StockMovement service from the receive flow when wiring that step).
 */
class PurchaseOrderService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    /**
     * @param array{supplier_id:int, currency?:string, warehouse_id?:int, marketplace_id?:int,
     *   expected_at?:string, notes?:string, shipping_total?:float, created_by?:int,
     *   items:array<int, array{name:string, sku?:string, product_id?:int, product_variant_id?:int,
     *   quantity:float, unit_cost:float, tax_amount?:float}>} $data
     */
    public function create(array $data): PurchaseOrder
    {
        $supplier = Supplier::findOrFail($data['supplier_id']);

        return DB::transaction(function () use ($data, $supplier) {
            $po = PurchaseOrder::create([
                'po_number' => $this->numbers->next('PO', 'PO-'),
                'supplier_id' => $supplier->id,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'marketplace_id' => $data['marketplace_id'] ?? null,
                'currency' => $data['currency'] ?? $supplier->currency ?? 'USD',
                'status' => 'draft',
                'shipping_total' => round((float) ($data['shipping_total'] ?? 0), 2),
                'expected_at' => $data['expected_at'] ?? null,
                'created_by' => $data['created_by'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $line) {
                $qty = (float) $line['quantity'];
                $cost = (float) $line['unit_cost'];
                $tax = round((float) ($line['tax_amount'] ?? 0), 2);
                $lineTotal = round($qty * $cost + $tax, 2);

                $po->items()->create([
                    'product_id' => $line['product_id'] ?? null,
                    'product_variant_id' => $line['product_variant_id'] ?? null,
                    'sku' => $line['sku'] ?? null,
                    'name' => $line['name'],
                    'quantity_ordered' => $qty,
                    'quantity_received' => 0,
                    'unit_cost' => $cost,
                    'tax_amount' => $tax,
                    'line_total' => $lineTotal,
                ]);
            }

            return $this->recomputeTotals($po);
        });
    }

    public function place(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== 'draft') {
            throw new \RuntimeException('Only a draft PO can be placed.');
        }

        $po->update(['status' => 'ordered', 'ordered_at' => now()]);

        return $po->fresh('items');
    }

    /**
     * @param array<int, array{item_id:int, quantity:float}> $lines
     */
    public function receive(PurchaseOrder $po, array $lines): PurchaseOrder
    {
        if (!in_array($po->status, ['ordered', 'partially_received'], true)) {
            throw new \RuntimeException('Only an ordered PO can receive stock.');
        }

        return DB::transaction(function () use ($po, $lines) {
            $items = $po->items()->get()->keyBy('id');

            foreach ($lines as $line) {
                $item = $items->get((int) ($line['item_id'] ?? 0));
                if (!$item) {
                    continue;
                }
                $add = max(0.0, (float) ($line['quantity'] ?? 0));
                $newReceived = min((float) $item->quantity_ordered, (float) $item->quantity_received + $add);
                $item->update(['quantity_received' => $newReceived]);
            }

            $po->refresh()->load('items');
            $fullyReceived = $po->items->every(fn ($i) => (float) $i->quantity_received >= (float) $i->quantity_ordered);
            $anyReceived = $po->items->contains(fn ($i) => (float) $i->quantity_received > 0);

            $po->update([
                'status' => $fullyReceived ? 'received' : ($anyReceived ? 'partially_received' : $po->status),
                'received_at' => $fullyReceived ? now() : $po->received_at,
            ]);

            return $po->fresh('items');
        });
    }

    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        if (in_array($po->status, ['received', 'cancelled'], true)) {
            throw new \RuntimeException('Cannot cancel a received/cancelled PO.');
        }

        $po->update(['status' => 'cancelled']);

        return $po->fresh('items');
    }

    private function recomputeTotals(PurchaseOrder $po): PurchaseOrder
    {
        $po->load('items');
        $subtotal = round((float) $po->items->sum(fn ($i) => (float) $i->quantity_ordered * (float) $i->unit_cost), 2);
        $tax = round((float) $po->items->sum('tax_amount'), 2);

        $po->update([
            'subtotal' => $subtotal,
            'tax_total' => $tax,
            'grand_total' => round($subtotal + $tax + (float) $po->shipping_total, 2),
        ]);

        return $po->fresh('items');
    }
}
