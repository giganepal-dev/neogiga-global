<?php

namespace App\Services\Vendor;

use App\Models\Marketplace\Vendor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VendorAuditLogger
{
    public function log(Vendor $vendor, string $action, ?User $user = null, ?string $entityType = null, ?int $entityId = null, array $old = [], array $new = [], ?string $notes = null, ?Request $request = null): void
    {
        if (! Schema::hasTable('vendor_audit_logs')) {
            return;
        }

        DB::table('vendor_audit_logs')->insert([
            'vendor_id' => $vendor->id,
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $old === [] ? null : json_encode($old),
            'new_values' => $new === [] ? null : json_encode($new),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'notes' => $notes,
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
