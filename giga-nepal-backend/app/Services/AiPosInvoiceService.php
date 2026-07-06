<?php

namespace App\Services;

use App\Models\AiBomBuild;
use App\Models\PosSession;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AiPosInvoiceService
{
    public function createFromBom(
        AiBomBuild $bomBuild,
        PosSession $posSession,
        ?User $customer = null
    ): PosSale {
        return DB::transaction(function () use ($bomBuild, $posSession, $customer) {
            $sale = PosSale::create([
                'pos_session_id' => $posSession->id,
                'warehouse_id' => $posSession->warehouse_id,
                'customer_id' => $customer?->id,
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
                'payment_status' => 'pending',
                'created_by_ai' => true,
                'ai_bom_build_id' => $bomBuild->id,
                'notes' => 'AI-generated sale from BOM: ' . substr($bomBuild->user_goal, 0, 100),
            ]);

            foreach ($bomBuild->items as $item) {
                if (!$item->product_id) {
                    continue;
                }

                PosSaleItem::create([
                    'pos_sale_id' => $sale->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => 0, // Will be calculated from product price
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'total' => 0,
                ]);
            }

            $sale->calculateTotals();
            
            return $sale->fresh(['items.product']);
        });
    }

    public function createQuickSale(
        PosSession $posSession,
        array $items,
        ?User $customer = null
    ): PosSale {
        return DB::transaction(function () use ($posSession, $items, $customer) {
            $sale = PosSale::create([
                'pos_session_id' => $posSession->id,
                'warehouse_id' => $posSession->warehouse_id,
                'customer_id' => $customer?->id,
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
                'payment_status' => 'pending',
                'created_by_ai' => false,
            ]);

            foreach ($items as $itemData) {
                PosSaleItem::create([
                    'pos_sale_id' => $sale->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'] ?? 1,
                    'unit_price' => $itemData['unit_price'] ?? 0,
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'total' => 0,
                ]);
            }

            $sale->calculateTotals();
            
            return $sale->fresh(['items.product']);
        });
    }

    public function processPayment(PosSale $sale, string $method, float $amount): PosPayment
    {
        return DB::transaction(function () use ($sale, $method, $amount) {
            $payment = PosPayment::create([
                'pos_sale_id' => $sale->id,
                'payment_method' => $method,
                'amount' => $amount,
                'status' => 'completed',
                'transaction_reference' => 'POS-' . uniqid(),
                'processed_at' => Carbon::now(),
            ]);

            $paidAmount = $sale->payments()->sum('amount');
            
            if ($paidAmount >= $sale->total_amount) {
                $sale->update(['payment_status' => 'paid']);
            } elseif ($paidAmount > 0) {
                $sale->update(['payment_status' => 'partial']);
            }

            return $payment;
        });
    }

    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Ymd') . '-';
        $lastSale = PosSale::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();
        
        $nextNumber = $lastSale ? (intval(substr($lastSale->invoice_number, -6)) + 1) : 1;
        
        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
