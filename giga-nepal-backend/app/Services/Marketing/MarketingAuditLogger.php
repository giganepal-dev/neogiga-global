<?php

namespace App\Services\Marketing;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class MarketingAuditLogger
{
    public function record(Request $request, string $action, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void
    {
        try {
            $user = $request->user();

            DB::table('marketing_admin_audit_logs')->insert([
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 2000),
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable) {
            // Audit logging must never break the primary admin workflow.
        }
    }
}
