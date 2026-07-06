<?php

namespace App\Services\POS;

use App\Services\Inventory\StockMovementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PosService
{
    public function __construct(private StockMovementService $stockMovements)
    {
    }

    public function openSession(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $terminalId = $data['pos_terminal_id'] ?? null;
            if (!$terminalId) {
                $terminalId = $this->defaultTerminal($data);
            }

            $existing = DB::table('pos_sessions')
                ->where('pos_terminal_id', $terminalId)
                ->where('status', 'open')
                ->first();
            if ($existing) {
                return ['id' => $existing->id, 'status' => 'open', 'session_number' => $existing->session_number, 'existing' => true];
            }

            $sessionNumber = 'POS-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            $id = DB::table('pos_sessions')->insertGetId([
                'pos_terminal_id' => $terminalId,
                'marketplace_id' => $data['marketplace_id'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => $data['user_id'] ?? null,
                'session_number' => $sessionNumber,
                'status' => 'open',
                'opening_cash' => $data['opening_cash'] ?? 0,
                'opened_at' => now(),
                'notes' => $data['notes'] ?? null,
                'metadata' => json_encode($data['metadata'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['id' => $id, 'status' => 'open', 'session_number' => $sessionNumber, 'existing' => false];
        });
    }

    public function closeSession(int $sessionId, array $data = []): array
    {
        $session = DB::table('pos_sessions')->where('id', $sessionId)->first();
        if (!$session || $session->status !== 'open') {
            return ['closed' => false, 'reason' => 'Session not open.'];
        }

        DB::table('pos_sessions')->where('id', $sessionId)->update([
            'status' => 'closed',
            'closing_cash' => $data['closing_cash'] ?? null,
            'closed_at' => now(),
            'notes' => $data['notes'] ?? $session->notes,
            'updated_at' => now(),
        ]);

        return ['closed' => true, 'session_id' => $sessionId];
    }

    public function searchProducts(array $filters)
    {
        return DB::table('products as p')
            ->leftJoin('inventory_stocks as s', 's.product_id', '=', 'p.id')
            ->selectRaw('p.id, p.name, p.slug, p.sku, p.base_price, p.sale_price, coalesce(sum(s.quantity_available),0) as quantity_available')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('p.name', 'like', "%{$term}%")->orWhere('p.sku', 'like', "%{$term}%");
            }))
            ->groupBy('p.id', 'p.name', 'p.slug', 'p.sku', 'p.base_price', 'p.sale_price')
            ->orderBy('p.name')
            ->limit((int) ($filters['limit'] ?? 25))
            ->get();
    }

    public function createSale(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $session = DB::table('pos_sessions')->where('id', $data['pos_session_id'])->lockForUpdate()->first();
            if (!$session || $session->status !== 'open') {
                throw new RuntimeException('POS session is not open.');
            }

            $items = [];
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $product = DB::table('products')->where('id', $item['product_id'])->first();
                if (!$product) {
                    throw new RuntimeException('Product not found.');
                }
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['unit_price'] ?? $product->sale_price ?? $product->base_price ?? 0);
                $lineTotal = $qty * $price;
                $subtotal += $lineTotal;
                $items[] = compact('product', 'qty', 'price', 'lineTotal') + ['raw' => $item];
            }

            $discount = (float) ($data['discount_amount'] ?? 0);
            $tax = (float) ($data['tax_amount'] ?? 0);
            $total = max(0, $subtotal - $discount + $tax);
            $reference = 'SALE-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

            $saleId = DB::table('pos_sales')->insertGetId([
                'pos_session_id' => $session->id,
                'marketplace_id' => $session->marketplace_id,
                'vendor_id' => $session->vendor_id,
                'warehouse_id' => $session->warehouse_id,
                'user_id' => $session->user_id,
                'sale_reference' => $reference,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'currency_code' => $data['currency_code'] ?? 'USD',
                'payment_status' => 'pending',
                'status' => 'completed',
                'customer_name' => $data['customer_name'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'metadata' => json_encode($data['metadata'] ?? []),
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($items as $row) {
                DB::table('pos_sale_items')->insert([
                    'pos_sale_id' => $saleId,
                    'product_id' => $row['product']->id,
                    'product_variant_id' => $row['raw']['product_variant_id'] ?? null,
                    'product_name' => $row['product']->name,
                    'product_sku' => $row['product']->sku,
                    'quantity' => $row['qty'],
                    'unit_price' => $row['price'],
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => $row['lineTotal'],
                    'metadata' => json_encode([]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->stockMovements->adjust([
                    'product_id' => $row['product']->id,
                    'variant_id' => $row['raw']['product_variant_id'] ?? null,
                    'warehouse_id' => $session->warehouse_id,
                    'quantity_change' => -$row['qty'],
                    'movement_type' => 'pos_sale',
                    'reference_type' => 'pos_sale',
                    'reference_id' => $saleId,
                    'notes' => 'POS sale '.$reference,
                ]);
            }

            return ['id' => $saleId, 'sale_reference' => $reference, 'total_amount' => $total, 'payment_status' => 'pending'];
        });
    }

    public function payment(int $saleId, array $data): array
    {
        return DB::transaction(function () use ($saleId, $data) {
            $sale = DB::table('pos_sales')->where('id', $saleId)->lockForUpdate()->first();
            if (!$sale) {
                throw new RuntimeException('Sale not found.');
            }

            $paymentId = DB::table('pos_payments')->insertGetId([
                'pos_sale_id' => $saleId,
                'amount' => $data['amount'],
                'currency_code' => $data['currency_code'] ?? $sale->currency_code,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_reference' => $data['payment_reference'] ?? 'PAY-'.Str::upper(Str::random(8)),
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'processed_at' => now(),
                'metadata' => json_encode($data['metadata'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $paid = (float) DB::table('pos_payments')->where('pos_sale_id', $saleId)->where('status', 'completed')->sum('amount');
            $status = $paid >= (float) $sale->total_amount ? 'paid' : ($paid > 0 ? 'partial' : 'pending');
            DB::table('pos_sales')->where('id', $saleId)->update(['payment_status' => $status, 'updated_at' => now()]);

            return ['payment_id' => $paymentId, 'sale_id' => $saleId, 'paid_amount' => $paid, 'payment_status' => $status];
        });
    }

    public function sale(int $saleId): ?object
    {
        $sale = DB::table('pos_sales')->where('id', $saleId)->first();
        if (!$sale) {
            return null;
        }
        $sale->items = DB::table('pos_sale_items')->where('pos_sale_id', $saleId)->get();
        $sale->payments = DB::table('pos_payments')->where('pos_sale_id', $saleId)->get();

        return $sale;
    }

    private function defaultTerminal(array $data): int
    {
        $terminal = DB::table('pos_terminals')->where('warehouse_id', $data['warehouse_id'])->where('status', 'active')->first();
        if ($terminal) {
            return $terminal->id;
        }

        return DB::table('pos_terminals')->insertGetId([
            'terminal_name' => $data['terminal_name'] ?? 'Default POS Terminal',
            'terminal_code' => 'POS-'.Str::upper(Str::random(8)),
            'marketplace_id' => $data['marketplace_id'] ?? null,
            'vendor_id' => $data['vendor_id'] ?? null,
            'warehouse_id' => $data['warehouse_id'],
            'status' => 'active',
            'location' => $data['location'] ?? null,
            'metadata' => json_encode(['created_by' => 'pos_service']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
