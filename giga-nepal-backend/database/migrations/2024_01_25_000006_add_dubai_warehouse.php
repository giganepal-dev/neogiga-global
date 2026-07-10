<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add Dubai warehouse to existing warehouses table
        DB::table('warehouses')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Dubai Distribution Center',
            'code' => 'DXB-DC',
            'type' => 'regional_distribution',
            'address_line_1' => 'Jebel Ali Free Zone (JAFZA)',
            'address_line_2' => 'South Zone, Plot S-40123',
            'city' => 'Dubai',
            'state_province' => 'Dubai',
            'postal_code' => '17027',
            'country' => 'AE',
            'country_name' => 'United Arab Emirates',
            'region' => 'Middle East',
            'latitude' => 24.9857,
            'longitude' => 55.0272,
            'capacity_units' => 80000,
            'current_stock_units' => 0,
            'manager_name' => null,
            'manager_email' => null,
            'manager_phone' => '+971-4-XXX-XXXX',
            'operating_hours_start' => '08:00',
            'operating_hours_end' => '20:00',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
            'is_primary' => false,
            'supports_cross_border' => true,
            'customs_clearance_enabled' => true,
            'cold_storage_available' => true,
            'hazmat_certified' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('warehouses')
            ->where('code', 'DXB-DC')
            ->delete();
    }
};
