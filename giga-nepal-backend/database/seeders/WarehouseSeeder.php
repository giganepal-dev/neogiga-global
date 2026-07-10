<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use Illuminate\Support\Str;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Middle East Distribution Center in Dubai, UAE
        Warehouse::create([
            'id' => (string) Str::uuid(),
            'name' => 'NeoGiga Middle East Distribution Center',
            'code' => 'ME-DC-DXB-001',
            'region' => 'Middle East',
            'country' => 'United Arab Emirates',
            'city' => 'Dubai',
            'address' => 'Jebel Ali Free Zone (JAFZA), Dubai',
            'postal_code' => '17030',
            'latitude' => 25.0118,
            'longitude' => 55.1203,
            'timezone' => 'Asia/Dubai',
            'currency_code' => 'AED',
            'status' => 'active',
            'contact_info' => json_encode([
                'phone' => '+971-4-888-8888',
                'email' => 'dubai.warehouse@neogiga.com',
                'manager' => 'Ahmed Al-Mansouri',
                'assistant_manager' => 'Rajesh Kumar',
            ]),
            'operating_hours' => json_encode([
                'sunday' => ['08:00', '20:00'],
                'monday' => ['08:00', '20:00'],
                'tuesday' => ['08:00', '20:00'],
                'wednesday' => ['08:00', '20:00'],
                'thursday' => ['08:00', '20:00'],
                'friday' => 'closed',
                'saturday' => ['10:00', '16:00'],
            ]),
            'capacity_units' => 50000,
            'current_stock_units' => 0,
            'is_distribution_center' => true,
            'is_fulfillment_center' => true,
            'allows_cross_border' => true,
        ]);

        // Create Nepal Main Warehouse (Kathmandu)
        Warehouse::create([
            'id' => (string) Str::uuid(),
            'name' => 'NeoGiga Nepal Main Warehouse',
            'code' => 'NP-MW-KTM-001',
            'region' => 'South Asia',
            'country' => 'Nepal',
            'city' => 'Kathmandu',
            'address' => 'Birtamod Industrial Area, Jhapa',
            'postal_code' => '57500',
            'latitude' => 26.6667,
            'longitude' => 87.9833,
            'timezone' => 'Asia/Kathmandu',
            'currency_code' => 'NPR',
            'status' => 'active',
            'contact_info' => json_encode([
                'phone' => '+977-21-555-555',
                'email' => 'kathmandu.warehouse@neogiga.com',
                'manager' => 'Sunita Sharma',
                'assistant_manager' => 'Binod Thapa',
            ]),
            'operating_hours' => json_encode([
                'sunday' => ['09:00', '18:00'],
                'monday' => ['09:00', '18:00'],
                'tuesday' => ['09:00', '18:00'],
                'wednesday' => ['09:00', '18:00'],
                'thursday' => ['09:00', '18:00'],
                'friday' => ['09:00', '15:00'],
                'saturday' => 'closed',
            ]),
            'capacity_units' => 30000,
            'current_stock_units' => 0,
            'is_distribution_center' => true,
            'is_fulfillment_center' => true,
            'allows_cross_border' => true,
        ]);

        // Create Secondary Nepal Warehouse (Pokhara)
        Warehouse::create([
            'id' => (string) Str::uuid(),
            'name' => 'NeoGiga Pokhara Regional Warehouse',
            'code' => 'NP-RW-PKR-001',
            'region' => 'South Asia',
            'country' => 'Nepal',
            'city' => 'Pokhara',
            'address' => 'Industrial Area, Pokhara',
            'postal_code' => '33700',
            'latitude' => 28.2096,
            'longitude' => 83.9856,
            'timezone' => 'Asia/Kathmandu',
            'currency_code' => 'NPR',
            'status' => 'active',
            'contact_info' => json_encode([
                'phone' => '+977-61-444-444',
                'email' => 'pokhara.warehouse@neogiga.com',
                'manager' => 'Ram Bahadur Gurung',
            ]),
            'operating_hours' => json_encode([
                'sunday' => ['09:00', '17:00'],
                'monday' => ['09:00', '17:00'],
                'tuesday' => ['09:00', '17:00'],
                'wednesday' => ['09:00', '17:00'],
                'thursday' => ['09:00', '17:00'],
                'friday' => ['09:00', '14:00'],
                'saturday' => 'closed',
            ]),
            'capacity_units' => 15000,
            'current_stock_units' => 0,
            'is_distribution_center' => false,
            'is_fulfillment_center' => true,
            'allows_cross_border' => false,
        ]);
    }
}
