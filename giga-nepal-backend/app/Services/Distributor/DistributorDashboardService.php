<?php

namespace App\Services\Distributor;

use App\Models\Distributor\Distributor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DistributorDashboardService
{
    public function overview(Distributor $distributor): array
    {
        $territoryDetails = Schema::hasTable('distributor_territories')
            ? DB::table('distributor_territories')
                ->leftJoin('countries', 'countries.id', '=', 'distributor_territories.country_id')
                ->leftJoin('regions', 'regions.id', '=', 'distributor_territories.region_id')
                ->leftJoin('cities', 'cities.id', '=', 'distributor_territories.city_id')
                ->where('distributor_territories.distributor_id', $distributor->id)
                ->orderBy('countries.name')
                ->select('distributor_territories.*', 'countries.name as country_name', 'countries.iso_code_2 as country_iso_code_2', 'regions.name as region_name', 'cities.name as city_name')
                ->get()
            : collect();

        return [
            'distributor' => $distributor->only(['id', 'name', 'slug', 'type', 'status', 'country_id', 'operating_scope']),
            'base_country_name' => $distributor->country_id ? DB::table('countries')->where('id', $distributor->country_id)->value('name') : null,
            'territories' => $territoryDetails->count(),
            'territory_details' => $territoryDetails,
            'leads' => Schema::hasTable('distributor_leads') ? DB::table('distributor_leads')->where('distributor_id', $distributor->id)->count() : 0,
            'customers' => Schema::hasTable('distributor_customers') ? DB::table('distributor_customers')->where('distributor_id', $distributor->id)->count() : 0,
            'orders' => Schema::hasTable('distributor_orders') ? DB::table('distributor_orders')->where('distributor_id', $distributor->id)->count() : 0,
            'pending_commission' => Schema::hasTable('distributor_commissions') ? (float) DB::table('distributor_commissions')->where('distributor_id', $distributor->id)->whereIn('status', ['pending', 'approved', 'payable'])->sum('commission_amount') : 0,
        ];
    }
}
