<?php

namespace App\Services;

use App\Models\VendorRfq;
use App\Models\VendorQuotation;
use App\Models\SellerOffer;
use Illuminate\Support\Facades\DB;

class RfqService
{
    /**
     * Get RFQs available for seller to quote on
     */
    public function getAvailableRfqs(int $vendorId, array $filters = [])
    {
        $vendor = \App\Models\Vendor::findOrFail($vendorId);
        
        $query = VendorRfq::where('status', 'open')
            ->where('deadline', '>', now())
            ->whereDoesntHave('quotations', function ($q) use ($vendorId) {
                $q->where('seller_id', $vendorId);
            });

        // Filter by marketplace if seller is approved
        if (isset($filters['marketplace'])) {
            $query->where('marketplace', $filters['marketplace']);
        }

        // Filter by product/MPN
        if (isset($filters['mpn'])) {
            $query->where('mpn', 'LIKE', '%' . $filters['mpn'] . '%');
        }

        // Filter by category
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->with(['product', 'category', 'createdBy'])
            ->orderBy('deadline', 'asc')
            ->paginate(20);
    }

    /**
     * Create quotation for RFQ
     */
    public function createQuotation(int $vendorId, int $rfqId, array $data): VendorQuotation
    {
        return DB::transaction(function () use ($vendorId, $rfqId, $data) {
            $rfq = VendorRfq::findOrFail($rfqId);

            // Verify RFQ is still open
            if ($rfq->status !== 'open') {
                throw new \Exception('RFQ is no longer open for quotations.');
            }

            if ($rfq->deadline < now()) {
                throw new \Exception('RFQ deadline has passed.');
            }

            // Check if seller already submitted quotation
            $existingQuotation = VendorQuotation::where('rfq_id', $rfqId)
                ->where('seller_id', $vendorId)
                ->first();

            if ($existingQuotation) {
                throw new \Exception('You have already submitted a quotation for this RFQ.');
            }

            // Validate warehouse
            if (isset($data['warehouse_id'])) {
                $warehouse = \App\Models\VendorWarehouse::where('id', $data['warehouse_id'])
                    ->where('vendor_id', $vendorId)
                    ->where('is_active', true)
                    ->where('is_verified', true)
                    ->firstOrFail();
            }

            $quotation = VendorQuotation::create([
                'rfq_id' => $rfqId,
                'seller_id' => $vendorId,
                'unit_price' => $data['unit_price'],
                'currency' => $data['currency'] ?? $rfq->currency,
                'quantity' => $data['quantity'] ?? $rfq->quantity_requested,
                'moq' => $data['moq'] ?? 1,
                'lead_time_days' => $data['lead_time_days'] ?? null,
                'date_code' => $data['date_code'] ?? null,
                'condition' => $data['condition'] ?? 'new',
                'packaging' => $data['packaging'] ?? 'original',
                'payment_terms' => $data['payment_terms'] ?? null,
                'shipping_terms' => $data['shipping_terms'] ?? null,
                'validity_days' => $data['validity_days'] ?? 30,
                'valid_until' => now()->addDays($data['validity_days'] ?? 30),
                'notes' => $data['notes'] ?? null,
                'status' => 'submitted',
                'warehouse_id' => $data['warehouse_id'] ?? null,
            ]);

            // Handle attachments
            if (isset($data['attachments'])) {
                foreach ($data['attachments'] as $attachment) {
                    $path = $attachment->store('quotations/' . $quotation->id, 'private');
                    $quotation->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $attachment->getClientOriginalName(),
                        'file_type' => $attachment->getMimeType(),
                    ]);
                }
            }

            event(new \App\Events\QuotationSubmitted($quotation));

            return $quotation->fresh();
        });
    }

    /**
     * Update draft quotation
     */
    public function updateDraftQuotation(VendorQuotation $quotation, int $vendorId, array $data): VendorQuotation
    {
        if ($quotation->seller_id !== $vendorId) {
            throw new \Exception('Unauthorized access to quotation.');
        }

        if ($quotation->status !== 'draft') {
            throw new \Exception('Only draft quotations can be updated.');
        }

        return DB::transaction(function () use ($quotation, $data) {
            $quotation->update($data);

            if (isset($data['validity_days'])) {
                $quotation->update(['valid_until' => now()->addDays($data['validity_days'])]);
            }

            return $quotation->fresh();
        });
    }

    /**
     * Submit draft quotation
     */
    public function submitQuotation(VendorQuotation $quotation): VendorQuotation
    {
        return DB::transaction(function () use ($quotation) {
            if ($quotation->status !== 'draft') {
                throw new \Exception('Only draft quotations can be submitted.');
            }

            $quotation->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            event(new \App\Events\QuotationSubmitted($quotation));

            return $quotation;
        });
    }

    /**
     * Revise quotation (if RFQ allows revisions)
     */
    public function reviseQuotation(VendorQuotation $quotation, int $vendorId, array $data): VendorQuotation
    {
        return DB::transaction(function () use ($quotation, $vendorId, $data) {
            if ($quotation->seller_id !== $vendorId) {
                throw new \Exception('Unauthorized access to quotation.');
            }

            if (!in_array($quotation->status, ['submitted', 'revision_requested'])) {
                throw new \Exception('Cannot revise quotation in current status.');
            }

            // Create revision record
            $revisionData = $quotation->replicate();
            $revisionData->parent_id = $quotation->id;
            $revisionData->status = 'revised';
            $revisionData->revision_number = ($quotation->revision_number ?? 0) + 1;
            $revisionData->save();

            // Update original quotation
            $quotation->update([
                'unit_price' => $data['unit_price'] ?? $quotation->unit_price,
                'quantity' => $data['quantity'] ?? $quotation->quantity,
                'lead_time_days' => $data['lead_time_days'] ?? $quotation->lead_time_days,
                'notes' => $data['notes'] ?? $quotation->notes,
                'status' => 'submitted',
                'revised_at' => now(),
            ]);

            event(new \App\Events\QuotationRevised($quotation));

            return $quotation->fresh();
        });
    }

    /**
     * Accept quotation (Buyer action - admin or customer)
     */
    public function acceptQuotation(VendorQuotation $quotation): void
    {
        DB::transaction(function () use ($quotation) {
            if ($quotation->status !== 'submitted') {
                throw new \Exception('Only submitted quotations can be accepted.');
            }

            $quotation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            // Convert to order if applicable
            if ($quotation->rfq && $quotation->rfq->convert_to_order) {
                $this->convertToOrder($quotation);
            }

            event(new \App\Events\QuotationAccepted($quotation));
        });
    }

    /**
     * Decline quotation
     */
    public function declineQuotation(VendorQuotation $quotation, ?string $reason = null): void
    {
        $quotation->update([
            'status' => 'declined',
            'decline_reason' => $reason,
            'declined_at' => now(),
        ]);
    }

    /**
     * Award quotation (mark as winner)
     */
    public function awardQuotation(VendorQuotation $quotation): void
    {
        DB::transaction(function () use ($quotation) {
            // Mark all other quotations for same RFQ as declined
            VendorQuotation::where('rfq_id', $quotation->rfq_id)
                ->where('id', '!=', $quotation->id)
                ->where('status', 'submitted')
                ->update(['status' => 'not_awarded']);

            $quotation->update([
                'status' => 'awarded',
                'awarded_at' => now(),
            ]);

            event(new \App\Events\QuotationAwarded($quotation));
        });
    }

    /**
     * Convert quotation to order
     */
    public function convertToOrder(VendorQuotation $quotation): \App\Models\VendorOrder
    {
        return DB::transaction(function () use ($quotation) {
            if ($quotation->status !== 'accepted' && $quotation->status !== 'awarded') {
                throw new \Exception('Only accepted or awarded quotations can be converted to orders.');
            }

            $rfq = $quotation->rfq;

            $order = \App\Models\VendorOrder::create([
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'customer_id' => $rfq->created_by,
                'customer_type' => $rfq->created_by_type,
                'status' => 'pending',
                'marketplace' => $rfq->marketplace,
                'currency' => $quotation->currency,
                'subtotal' => $quotation->unit_price * $quotation->quantity,
                'shipping_cost' => 0,
                'tax_amount' => 0,
                'total_amount' => $quotation->unit_price * $quotation->quantity,
                'payment_status' => 'pending',
                'payment_method' => $quotation->payment_terms ?? 'invoice',
                'shipping_terms' => $quotation->shipping_terms ?? 'FOB',
                'notes' => $quotation->notes,
                'source_type' => VendorQuotation::class,
                'source_id' => $quotation->id,
            ]);

            // Create order item
            $offer = SellerOffer::where('seller_id', $quotation->seller_id)
                ->where('product_id', $rfq->product_id)
                ->first();

            $order->items()->create([
                'product_id' => $rfq->product_id,
                'offer_id' => $offer?->id,
                'quantity' => $quotation->quantity,
                'unit_price' => $quotation->unit_price,
                'total_price' => $quotation->unit_price * $quotation->quantity,
            ]);

            $quotation->update([
                'order_id' => $order->id,
                'converted_to_order' => true,
            ]);

            event(new \App\Events\OrderCreatedFromQuotation($order));

            return $order;
        });
    }

    /**
     * Get quotations for seller
     */
    public function getSellerQuotations(int $vendorId, array $filters = [])
    {
        $query = VendorQuotation::where('seller_id', $vendorId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['rfq_id'])) {
            $query->where('rfq_id', $filters['rfq_id']);
        }

        return $query->with(['rfq.product', 'rfq.createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    /**
     * Get quotation statistics
     */
    public function getQuotationStats(int $vendorId, ?string $period = '30_days'): array
    {
        $dateFrom = now()->subDays(30);
        if ($period === '7_days') $dateFrom = now()->subDays(7);
        if ($period === '90_days') $dateFrom = now()->subDays(90);

        $query = VendorQuotation::where('seller_id', $vendorId)
            ->where('created_at', '>=', $dateFrom);

        $stats = [
            'total_quotations' => $query->count(),
            'submitted' => (clone $query)->where('status', 'submitted')->count(),
            'accepted' => (clone $query)->where('status', 'accepted')->count(),
            'awarded' => (clone $query)->where('status', 'awarded')->count(),
            'declined' => (clone $query)->where('status', 'declined')->count(),
            'expired' => (clone $query)->where('valid_until', '<', now())->count(),
            'conversion_rate' => 0,
        ];

        $total = $stats['total_quotations'];
        if ($total > 0) {
            $stats['conversion_rate'] = round(($stats['awarded'] / $total) * 100, 2);
        }

        return $stats;
    }
}
