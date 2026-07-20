<?php

namespace App\Services\POS;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PosTerminalService
{
    public function listForMarketplace(?int $marketplaceId = null): array
    {
        if (! Schema::hasTable('pos_terminals')) {
            return [];
        }

        return DB::table('pos_terminals')
            ->when($marketplaceId, fn ($q) => $q->where('marketplace_id', $marketplaceId))
            ->where('status', 'active')
            ->orderBy('terminal_name')
            ->get(['id', 'terminal_name', 'terminal_code', 'marketplace_id', 'warehouse_id', 'location'])
            ->all();
    }

    public function resolveForSession(array $data): int
    {
        if (! empty($data['pos_terminal_id'])) {
            $terminal = DB::table('pos_terminals')->where('id', $data['pos_terminal_id'])->where('status', 'active')->first();
            if (! $terminal) {
                throw new \RuntimeException('POS terminal not found or inactive.');
            }
            if (! empty($data['marketplace_id']) && $terminal->marketplace_id && (int) $terminal->marketplace_id !== (int) $data['marketplace_id']) {
                throw new \RuntimeException('Terminal is not scoped to the requested marketplace.');
            }

            return (int) $terminal->id;
        }

        $query = DB::table('pos_terminals')->where('warehouse_id', $data['warehouse_id'])->where('status', 'active');
        if (! empty($data['marketplace_id'])) {
            $query->where('marketplace_id', $data['marketplace_id']);
        }
        $terminal = $query->first();
        if ($terminal) {
            return (int) $terminal->id;
        }

        return DB::table('pos_terminals')->insertGetId([
            'terminal_name' => $data['terminal_name'] ?? 'Default POS Terminal',
            'terminal_code' => 'POS-'.Str::upper(Str::random(8)),
            'marketplace_id' => $data['marketplace_id'] ?? null,
            'vendor_id' => $data['vendor_id'] ?? null,
            'warehouse_id' => $data['warehouse_id'],
            'status' => 'active',
            'location' => $data['location'] ?? null,
            'metadata' => json_encode(['created_by' => 'pos_terminal_service']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
