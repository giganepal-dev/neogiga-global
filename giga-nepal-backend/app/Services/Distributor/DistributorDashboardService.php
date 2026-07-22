<?php

namespace App\Services\Distributor;

use App\Models\Distributor\Distributor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DistributorDashboardService
{
    public function overview(Distributor $distributor): array
    {
        return [
            'distributor' => $distributor->only(['id', 'name', 'slug', 'type', 'status', 'country_id', 'operating_scope']),
            'territories' => Schema::hasTable('distributor_territories') ? DB::table('distributor_territories')->where('distributor_id', $distributor->id)->count() : 0,
            'leads' => Schema::hasTable('distributor_leads') ? DB::table('distributor_leads')->where('distributor_id', $distributor->id)->count() : 0,
            'customers' => Schema::hasTable('distributor_customers') ? DB::table('distributor_customers')->where('distributor_id', $distributor->id)->count() : 0,
            'orders' => Schema::hasTable('distributor_orders') ? DB::table('distributor_orders')->where('distributor_id', $distributor->id)->count() : 0,
            'pending_commission' => Schema::hasTable('distributor_commissions') ? (float) DB::table('distributor_commissions')->where('distributor_id', $distributor->id)->whereIn('status', ['pending', 'approved', 'payable'])->sum('commission_amount') : 0,
        ];
    }
}
