<?php

namespace App\Services\Seller;

use App\Models\Marketplace\VendorProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SellerProductDetailService
{
    public function productId(VendorProduct $vendorProduct): int
    {
        abort_if(! $vendorProduct->product_id, 422, 'This seller product is not linked to a catalog product yet.');
        return (int) $vendorProduct->product_id;
    }

    public function log(VendorProduct $vendorProduct, ?int $userId, string $action, array $values = []): void
    {
        if (! Schema::hasTable('vendor_audit_logs')) {
            return;
        }

        DB::table('vendor_audit_logs')->insert([
            'vendor_id' => $vendorProduct->vendor_id,
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => 'vendor_product',
            'entity_id' => $vendorProduct->id,
            'old_values' => null,
            'new_values' => json_encode($values),
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 1000),
            'notes' => 'Seller product detail update',
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
