<?php

namespace App\Services\Vendor;

use App\Models\Marketplace\Vendor;
use App\Models\Marketplace\VendorMarketplaceApproval;
use App\Models\Marketplace\VendorPayout;
use App\Models\Marketplace\VendorProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class VendorApprovalService
{
    public function __construct(private readonly VendorAuditLogger $audit)
    {
    }

    public function approveVendor(Vendor $vendor, ?User $user = null, ?Request $request = null): Vendor
    {
        $old = $vendor->only(['status', 'commerce_status', 'is_verified', 'verified_at']);

        $changes = [
            'status' => 'active',
            'is_verified' => true,
            'verified_at' => now(),
        ];
        if (Schema::hasColumn('vendors', 'commerce_status')) {
            $changes['commerce_status'] = 'verified';
        }

        $vendor->forceFill($changes)->save();

        $this->audit->log($vendor, 'vendor.approved', $user, 'vendor', $vendor->id, $old, $vendor->fresh()->only(['status', 'commerce_status', 'is_verified', 'verified_at']), null, $request);

        return $vendor->fresh();
    }

    public function rejectVendor(Vendor $vendor, string $reason, ?User $user = null, ?Request $request = null): Vendor
    {
        $old = $vendor->only(['status', 'commerce_status']);

        $changes = [
            'status' => 'rejected',
            'metadata' => array_merge($vendor->metadata ?? [], ['rejection_reason' => $reason]),
        ];
        if (Schema::hasColumn('vendors', 'commerce_status')) {
            $changes['commerce_status'] = 'rejected';
        }

        $vendor->forceFill($changes)->save();

        $this->audit->log($vendor, 'vendor.rejected', $user, 'vendor', $vendor->id, $old, $vendor->fresh()->only(['status', 'commerce_status', 'metadata']), $reason, $request);

        return $vendor->fresh();
    }

    public function suspendVendor(Vendor $vendor, string $reason, ?User $user = null, ?Request $request = null): Vendor
    {
        $old = $vendor->only(['status', 'commerce_status']);

        $changes = [
            'status' => 'suspended',
            'metadata' => array_merge($vendor->metadata ?? [], ['suspension_reason' => $reason]),
        ];
        if (Schema::hasColumn('vendors', 'commerce_status')) {
            $changes['commerce_status'] = 'suspended';
        }

        $vendor->forceFill($changes)->save();

        $this->audit->log($vendor, 'vendor.suspended', $user, 'vendor', $vendor->id, $old, $vendor->fresh()->only(['status', 'commerce_status', 'metadata']), $reason, $request);

        return $vendor->fresh();
    }

    public function approveMarketplace(VendorMarketplaceApproval $approval, ?User $user = null, ?Request $request = null): VendorMarketplaceApproval
    {
        $old = $approval->only(['status', 'reviewed_by', 'reviewed_at', 'approved_at']);
        $approval->forceFill([
            'status' => 'approved',
            'reviewed_by' => $user?->id,
            'reviewed_at' => now(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ])->save();
        $this->audit->log($approval->vendor, 'vendor_marketplace.approved', $user, 'vendor_marketplace_approval', $approval->id, $old, $approval->fresh()->only(['status', 'reviewed_by', 'reviewed_at', 'approved_at']), null, $request);

        return $approval->fresh();
    }

    public function rejectMarketplace(VendorMarketplaceApproval $approval, string $reason, ?User $user = null, ?Request $request = null): VendorMarketplaceApproval
    {
        $old = $approval->only(['status', 'reviewed_by', 'reviewed_at', 'rejection_reason']);
        $approval->forceFill([
            'status' => 'rejected',
            'reviewed_by' => $user?->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ])->save();
        $this->audit->log($approval->vendor, 'vendor_marketplace.rejected', $user, 'vendor_marketplace_approval', $approval->id, $old, $approval->fresh()->only(['status', 'reviewed_by', 'reviewed_at', 'rejection_reason']), $reason, $request);

        return $approval->fresh();
    }

    public function approveProduct(VendorProduct $product, ?User $user = null, ?Request $request = null): VendorProduct
    {
        $old = $product->only(['status', 'reviewed_by', 'reviewed_at']);
        $product->forceFill([
            'status' => 'approved',
            'reviewed_by' => $user?->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ])->save();
        $this->audit->log($product->vendor, 'vendor_product.approved', $user, 'vendor_product', $product->id, $old, $product->fresh()->only(['status', 'reviewed_by', 'reviewed_at']), null, $request);

        return $product->fresh();
    }

    public function rejectProduct(VendorProduct $product, string $reason, ?User $user = null, ?Request $request = null): VendorProduct
    {
        $old = $product->only(['status', 'reviewed_by', 'reviewed_at', 'rejection_reason']);
        $product->forceFill([
            'status' => 'rejected',
            'reviewed_by' => $user?->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ])->save();
        $this->audit->log($product->vendor, 'vendor_product.rejected', $user, 'vendor_product', $product->id, $old, $product->fresh()->only(['status', 'reviewed_by', 'reviewed_at', 'rejection_reason']), $reason, $request);

        return $product->fresh();
    }

    public function markPayoutPaid(VendorPayout $payout, ?User $user = null, ?Request $request = null): VendorPayout
    {
        $old = $payout->only(['status', 'paid_at', 'marked_paid_by']);
        $payout->forceFill([
            'status' => 'paid',
            'paid_at' => now(),
            'marked_paid_by' => $user?->id,
        ])->save();
        $this->audit->log($payout->vendor, 'vendor_payout.marked_paid', $user, 'vendor_payout', $payout->id, $old, $payout->fresh()->only(['status', 'paid_at', 'marked_paid_by']), null, $request);

        return $payout->fresh();
    }
}
