<?php

namespace App\Services\Product;

use App\Models\Marketplace\VendorProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductApprovalService
{
    public function approveVendorProduct(VendorProduct $vendorProduct, Request $request): VendorProduct
    {
        $vendorProduct->forceFill([
            'status' => 'approved',
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ])->save();

        if ($vendorProduct->product_id && Schema::hasTable('products')) {
            DB::table('products')->where('id', $vendorProduct->product_id)->update([
                'status' => 'approved',
                'approval_status' => 'approved',
                'approved_by' => $request->user()?->id,
                'approved_at' => now(),
                'rejection_reason' => null,
                'updated_at' => now(),
            ]);
        }

        $this->log($vendorProduct, $request, 'admin.vendor_product.approved');

        return $vendorProduct->fresh();
    }

    public function rejectVendorProduct(VendorProduct $vendorProduct, Request $request, ?string $reason): VendorProduct
    {
        $vendorProduct->forceFill([
            'status' => 'rejected',
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ])->save();

        if ($vendorProduct->product_id && Schema::hasTable('products')) {
            DB::table('products')->where('id', $vendorProduct->product_id)->update([
                'status' => 'rejected',
                'approval_status' => 'rejected',
                'rejection_reason' => $reason,
                'updated_at' => now(),
            ]);
        }

        $this->log($vendorProduct, $request, 'admin.vendor_product.rejected', ['reason' => $reason]);

        return $vendorProduct->fresh();
    }

    private function log(VendorProduct $vendorProduct, Request $request, string $action, array $extra = []): void
    {
        if (! Schema::hasTable('vendor_audit_logs')) {
            return;
        }

        DB::table('vendor_audit_logs')->insert([
            'vendor_id' => $vendorProduct->vendor_id,
            'user_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => 'vendor_product',
            'entity_id' => $vendorProduct->id,
            'old_values' => null,
            'new_values' => json_encode(['status' => $vendorProduct->status] + $extra),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'notes' => 'Admin product review action',
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
