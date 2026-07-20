<?php

namespace App\Services\Distributor;

use App\Models\Distributor\Distributor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DistributorCommissionService
{
    public function summary(Distributor $distributor): array
    {
        if (! Schema::hasTable('distributor_commissions')) {
            return [
                'pending' => 0.0,
                'approved' => 0.0,
                'paid' => 0.0,
                'total_earned' => 0.0,
                'currency_code' => 'USD',
            ];
        }

        $rows = DB::table('distributor_commissions')
            ->select('status', DB::raw('sum(commission_amount) as total'))
            ->where('distributor_id', $distributor->id)
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $pending = (float) ($rows->get('pending')?->total ?? 0);
        $approved = (float) ($rows->get('approved')?->total ?? 0) + (float) ($rows->get('payable')?->total ?? 0);
        $paid = (float) ($rows->get('paid')?->total ?? 0);

        return [
            'pending' => $pending,
            'approved' => $approved,
            'paid' => $paid,
            'total_earned' => $pending + $approved + $paid,
            'currency_code' => 'USD',
        ];
    }

    public function paginateCommissions(Distributor $distributor, int $perPage = 20): LengthAwarePaginator
    {
        if (! Schema::hasTable('distributor_commissions')) {
            return new Paginator([], 0, $perPage);
        }

        return DB::table('distributor_commissions')
            ->where('distributor_id', $distributor->id)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function paginatePayouts(Distributor $distributor, int $perPage = 20): LengthAwarePaginator
    {
        if (! Schema::hasTable('distributor_payouts')) {
            return new Paginator([], 0, $perPage);
        }

        return DB::table('distributor_payouts')
            ->where('distributor_id', $distributor->id)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function downlines(Distributor $distributor): Collection
    {
        if (! Schema::hasTable('distributor_downlines') || ! Schema::hasTable('distributors')) {
            return collect();
        }

        return DB::table('distributor_downlines as dl')
            ->join('distributors as child', 'child.id', '=', 'dl.child_distributor_id')
            ->where('dl.parent_distributor_id', $distributor->id)
            ->select([
                'dl.id',
                'dl.relationship_type',
                'dl.created_at',
                'child.id as child_id',
                'child.name as child_name',
                'child.email as child_email',
                'child.status as child_status',
                'child.type as child_type',
            ])
            ->orderBy('child.name')
            ->get();
    }

    public function downlineStats(Distributor $distributor): array
    {
        $downlines = $this->downlines($distributor);

        return [
            'total' => $downlines->count(),
            'active' => $downlines->where('child_status', 'approved')->count(),
            'pending' => $downlines->whereIn('child_status', ['pending', 'review'])->count(),
        ];
    }
}
