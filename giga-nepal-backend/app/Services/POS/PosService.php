<?php

namespace App\Services\POS;

use App\Services\Inventory\StockMovementService;
use App\Services\Product\ProductPublicationGate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class PosService
{
    public function __construct(
        private StockMovementService $stockMovements,
        private ProductPublicationGate $publicationGate,
        private PosTerminalService $terminals,
        private PosReceiptService $receipts,
    ) {}

    public function openSession(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $terminalId = $this->terminals->resolveForSession($data);

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
        if (! $session || $session->status !== 'open') {
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
        $query = DB::table('products as p')
            ->leftJoin('inventory_stocks as s', 's.product_id', '=', 'p.id')
            ->selectRaw('p.id, p.name, p.slug, p.sku, p.base_price, p.sale_price, coalesce(sum(s.quantity_available),0) as quantity_available')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('p.name', 'like', "%{$term}%")->orWhere('p.sku', 'like', "%{$term}%");
            }))
            ->groupBy('p.id', 'p.name', 'p.slug', 'p.sku', 'p.base_price', 'p.sale_price');
        $this->publicationGate->apply($query, 'p');

        return $query
            ->orderBy('p.name')
            ->limit((int) ($filters['limit'] ?? 25))
            ->get();
    }

    public function createSale(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $session = DB::table('pos_sessions')->where('id', $data['pos_session_id'])->lockForUpdate()->first();
            if (! $session || $session->status !== 'open') {
                throw new RuntimeException('POS session is not open.');
            }

            $items = [];
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $productQuery = DB::table('products')->where('id', $item['product_id']);
                $this->publicationGate->apply($productQuery);
                $product = $productQuery->first();
                if (! $product) {
                    throw new RuntimeException('Product is not publicly approved for sale.');
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
            $qrToken = Str::lower(Str::random(32));

            $salePayload = [
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
            ];

            if (Schema::hasColumn('pos_sales', 'receipt_qr_token')) {
                $salePayload['receipt_qr_token'] = $qrToken;
            }
            if (Schema::hasColumn('pos_sales', 'pos_customer_account_id') && ! empty($data['pos_customer_account_id'])) {
                $salePayload['pos_customer_account_id'] = $data['pos_customer_account_id'];
            }

            $saleId = DB::table('pos_sales')->insertGetId($salePayload);

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

            return [
                'id' => $saleId,
                'sale_reference' => $reference,
                'total_amount' => $total,
                'payment_status' => 'pending',
                'receipt_qr_token' => $qrToken,
                'receipt_url' => $this->receipts->receiptUrl($qrToken),
            ];
        });
    }

    public function payment(int $saleId, array $data): array
    {
        return DB::transaction(function () use ($saleId, $data) {
            $sale = DB::table('pos_sales')->where('id', $saleId)->lockForUpdate()->first();
            if (! $sale) {
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

    public function refund(int $saleId, array $data, ?int $processedBy = null): array
    {
        if (! Schema::hasTable('pos_refunds')) {
            throw new RuntimeException('POS refund table is not available.');
        }

        $amount = $this->formatPosAmount($this->posAmountUnits($data['amount']));
        $idempotencyKey = trim((string) ($data['idempotency_key'] ?? ''));
        abort_if($idempotencyKey === '', 422, 'Idempotency key is required.');
        $requestFingerprint = hash('sha256', json_encode([
            'pos_sale_id' => $saleId,
            'processed_by' => $processedBy,
            'amount' => $amount,
            'refund_method' => $data['refund_method'],
            'reason' => trim((string) $data['reason']),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return DB::transaction(function () use ($saleId, $data, $amount, $idempotencyKey, $requestFingerprint, $processedBy): array {
            $row = DB::table('pos_sales')->where('id', $saleId)->lockForUpdate()->first();
            if (! $row) {
                throw new RuntimeException('POS sale not found.');
            }

            $existing = DB::table('pos_refunds')
                ->where('pos_sale_id', $saleId)
                ->where('metadata->idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $metadata = json_decode((string) $existing->metadata, true) ?: [];
                if (($metadata['request_fingerprint'] ?? null) !== $requestFingerprint) {
                    throw new RuntimeException('Idempotency key was already used for a different refund request.');
                }

                return ['refund_id' => (int) $existing->id, 'replayed' => true];
            }

            $saleTotal = $this->posAmountUnits($row->total_amount);
            $alreadyRefunded = $this->posAmountUnits(DB::table('pos_refunds')
                ->where('pos_sale_id', $saleId)
                ->whereIn('status', config('pos.refund_statuses', ['recorded', 'processed']))
                ->sum('amount'));
            $requested = $this->posAmountUnits($amount);
            $remaining = max(0, $saleTotal - $alreadyRefunded);
            if ($requested > $remaining) {
                throw new RuntimeException('Refund exceeds remaining sale total.');
            }

            $refundId = DB::table('pos_refunds')->insertGetId([
                'pos_sale_id' => $saleId,
                'amount' => $amount,
                'currency_code' => $row->currency_code ?? 'USD',
                'refund_method' => $data['refund_method'],
                'reason' => $data['reason'],
                'status' => 'recorded',
                'processed_by' => $processedBy,
                'processed_at' => now(),
                'metadata' => json_encode([
                    'saved_via' => 'pos_api',
                    'gateway_action' => 'not_sent',
                    'idempotency_key' => $idempotencyKey,
                    'request_fingerprint' => $requestFingerprint,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $newRefunded = $alreadyRefunded + $requested;
            DB::table('pos_sales')->where('id', $saleId)->update([
                'payment_status' => $newRefunded >= $saleTotal ? 'refunded' : 'partial_refund',
                'status' => $newRefunded >= $saleTotal ? 'refunded' : $row->status,
                'updated_at' => now(),
            ]);

            return ['refund_id' => $refundId, 'replayed' => false];
        }, 3);
    }

    public function sale(int $saleId): ?object
    {
        $sale = DB::table('pos_sales')->where('id', $saleId)->first();
        if (! $sale) {
            return null;
        }
        $sale->items = DB::table('pos_sale_items')->where('pos_sale_id', $saleId)->get();
        $sale->payments = DB::table('pos_payments')->where('pos_sale_id', $saleId)->get();
        if (Schema::hasTable('pos_refunds')) {
            $sale->refunds = DB::table('pos_refunds')->where('pos_sale_id', $saleId)->get();
        }

        return $sale;
    }

    private function posAmountUnits(mixed $value): int
    {
        $decimal = trim((string) $value);
        if (! preg_match('/^(\d+)(?:\.(\d+))?$/', $decimal, $matches)) {
            throw new RuntimeException('POS amount must be a non-negative decimal value.');
        }

        $fraction = $matches[2] ?? '';
        if (strlen($fraction) > 4 && trim(substr($fraction, 4), '0') !== '') {
            throw new RuntimeException('POS amount supports at most four decimal places.');
        }

        $whole = (int) $matches[1];
        $fractionUnits = (int) str_pad(substr($fraction, 0, 4), 4, '0');

        return ($whole * 10000) + $fractionUnits;
    }

    private function formatPosAmount(int $units): string
    {
        return intdiv($units, 10000).'.'.str_pad((string) ($units % 10000), 4, '0', STR_PAD_LEFT);
    }
}
