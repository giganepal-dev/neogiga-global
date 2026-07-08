<?php

namespace App\Services\Bom;

use App\Models\Bom\BomProject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BomCartService
{
    public function recordConversion(BomProject $project, ?int $userId, array $estimate): array
    {
        if (! Schema::hasTable('bom_cart_conversions')) {
            return ['recorded' => false, 'reason' => 'BOM cart conversion migration is pending.'];
        }

        $id = DB::table('bom_cart_conversions')->insertGetId([
            'bom_project_id' => $project->id,
            'user_id' => $userId,
            'name' => 'bom_project_add_to_cart',
            'status' => 'pending_cart_integration',
            'payload' => json_encode(['estimate' => $estimate]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['recorded' => true, 'conversion_id' => $id, 'status' => 'pending_cart_integration'];
    }
}
