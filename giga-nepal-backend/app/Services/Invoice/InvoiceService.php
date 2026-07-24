<?php

namespace App\Services\Invoice;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InvoiceService
{
    public function createFromOrder(int $orderId, array $options = []): Invoice
    {
        $order = DB::table('orders')->find($orderId);
        abort_unless($order, 404, 'Order not found.');

        $items = DB::table('order_items')
            ->where('order_id', $orderId)
            ->get();

        $subtotal = $items->sum('total');
        $taxRate = $options['tax_rate'] ?? 0.13;
        $taxAmount = round($subtotal * $taxRate, 2);
        $shippingAmount = (float) ($order->shipping_amount ?? 0);
        $discountAmount = (float) ($order->discount_amount ?? 0);
        $totalAmount = $subtotal + $taxAmount + $shippingAmount - $discountAmount;

        $invoiceNumber = $this->generateInvoiceNumber($order->marketplace_id);
        $qrToken = Str::random(64);
        $verificationHash = hash('sha256', $invoiceNumber . '|' . $qrToken . '|' . config('app.key'));

        $invoiceId = DB::table('invoices')->insertGetId([
            'invoice_number' => $invoiceNumber,
            'qr_token' => $qrToken,
            'verification_hash' => $verificationHash,
            'order_id' => $orderId,
            'marketplace_id' => $order->marketplace_id,
            'vendor_id' => $order->vendor_id ?? null,
            'user_id' => $order->user_id,
            'status' => 'issued',
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'currency_code' => $order->currency_code ?? 'USD',
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
            'billing_name' => $order->billing_name ?? $order->shipping_name ?? null,
            'billing_email' => $order->billing_email ?? null,
            'billing_address' => $order->billing_address ?? null,
            'shipping_name' => $order->shipping_name ?? null,
            'shipping_address' => $order->shipping_address ?? null,
            'notes' => $options['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($items as $item) {
            DB::table('invoice_items')->insert([
                'invoice_id' => $invoiceId,
                'product_id' => $item->product_id ?? null,
                'product_variant_id' => $item->product_variant_id ?? null,
                'product_name' => $item->product_name ?? $item->name ?? 'Item',
                'product_sku' => $item->product_sku ?? $item->sku ?? null,
                'quantity' => $item->quantity ?? 1,
                'unit_price' => $item->unit_price ?? $item->price ?? 0,
                'tax_amount' => round(($item->total ?? $item->price ?? 0) * $taxRate, 2),
                'discount_amount' => $item->discount_amount ?? 0,
                'total_amount' => $item->total ?? $item->price ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return Invoice::findOrFail($invoiceId);
    }

    public function verify(string $invoiceNumber, string $token): array
    {
        $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

        if (! $invoice) {
            return ['valid' => false, 'reason' => 'Invoice not found.'];
        }

        $expectedHash = hash('sha256', $invoiceNumber . '|' . $token . '|' . config('app.key'));

        if (! hash_equals($expectedHash, $invoice->verification_hash ?? '')) {
            return ['valid' => false, 'reason' => 'Invalid verification token.'];
        }

        return [
            'valid' => true,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status,
            'issued_at' => $invoice->issued_at,
            'total_amount' => $invoice->total_amount,
            'currency_code' => $invoice->currency_code,
            'marketplace' => $invoice->marketplace?->name ?? 'NeoGiga',
        ];
    }

    public function getVerificationUrl(Invoice $invoice): string
    {
        return url("/verify/invoice/{$invoice->invoice_number}?token={$invoice->qr_token}");
    }

    public function createCreditNote(int $invoiceId, string $reason, array $options = []): Invoice
    {
        $original = Invoice::findOrFail($invoiceId);
        abort_if($original->status === 'credit_note', 400, 'Invoice is already a credit note.');

        $creditNoteNumber = $this->generateInvoiceNumber($original->marketplace_id, 'CN');
        $qrToken = Str::random(64);
        $verificationHash = hash('sha256', $creditNoteNumber . '|' . $qrToken . '|' . config('app.key'));

        $creditNoteId = DB::table('invoices')->insertGetId([
            'invoice_number' => $creditNoteNumber,
            'qr_token' => $qrToken,
            'verification_hash' => $verificationHash,
            'order_id' => $original->order_id,
            'marketplace_id' => $original->marketplace_id,
            'vendor_id' => $original->vendor_id,
            'user_id' => $original->user_id,
            'status' => 'credit_note',
            'subtotal' => -$original->subtotal,
            'tax_amount' => -$original->tax_amount,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => -$original->total_amount,
            'currency_code' => $original->currency_code,
            'issued_at' => now(),
            'due_at' => null,
            'paid_at' => now(),
            'billing_name' => $original->billing_name,
            'billing_email' => $original->billing_email,
            'billing_address' => $original->billing_address,
            'notes' => $reason,
            'credit_note_id' => $original->id,
            'credit_note_reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Copy items with negated amounts
        $items = DB::table('invoice_items')->where('invoice_id', $invoiceId)->get();
        foreach ($items as $item) {
            DB::table('invoice_items')->insert([
                'invoice_id' => $creditNoteId,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'quantity' => $item->quantity,
                'unit_price' => -$item->unit_price,
                'tax_amount' => -$item->tax_amount,
                'discount_amount' => 0,
                'total_amount' => -$item->total_amount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Link original to credit note
        DB::table('invoices')->where('id', $invoiceId)->update([
            'credit_note_id' => $creditNoteId,
            'updated_at' => now(),
        ]);

        return Invoice::findOrFail($creditNoteId);
    }

    private function generateInvoiceNumber(?int $marketplaceId, string $prefix = 'INV'): string
    {
        $year = date('Y');
        $marketplaceCode = 'GIG';

        if ($marketplaceId) {
            $marketplaceCode = DB::table('marketplaces')
                ->where('id', $marketplaceId)
                ->value('code') ?? 'GIG';
        }

        $sequence = DB::table('invoices')
            ->whereYear('created_at', $year)
            ->max('id') ?? 0;

        return "{$prefix}-{$marketplaceCode}-{$year}-" . str_pad($sequence + 1, 5, '0', STR_PAD_LEFT);
    }
}
