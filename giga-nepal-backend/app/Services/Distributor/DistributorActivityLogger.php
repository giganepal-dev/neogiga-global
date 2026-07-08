<?php

namespace App\Services\Distributor;

use App\Models\Distributor\Distributor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DistributorActivityLogger
{
    public function log(?Distributor $distributor, string $action, ?User $user = null, ?string $entityType = null, ?int $entityId = null, array $old = [], array $new = [], ?Request $request = null): void
    {
        if (! Schema::hasTable('distributor_activity_logs')) {
            return;
        }

        DB::table('distributor_activity_logs')->insert([
            'distributor_id' => $distributor?->id,
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $old === [] ? null : json_encode($old),
            'new_values' => $new === [] ? null : json_encode($new),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
