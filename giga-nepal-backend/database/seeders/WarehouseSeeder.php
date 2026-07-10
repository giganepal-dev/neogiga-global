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
        // Create Shenzhen, China - Primary Distribution Center (Asia)
        Warehouse::create([
            'id' => (string) Str::uuid(),
            'name' => 'NeoGiga Shenzhen Global Distribution Center',
            'code' => 'CN-GDC-SZX-001',
            'region' => 'East Asia',
            'country' => 'China',
            'city' => 'Shenzhen',
            'address' => 'Qianhai Free Trade Zone, Shenzhen, Guangdong Province',
            'postal_code' => '518000',
            'latitude' => 22.5431,
            'longitude' => 114.0579,
            'timezone' => 'Asia/Shanghai',
            'currency_code' => 'CNY',
            'status' => 'active',
            'contact_info' => json_encode([
                'phone' => '+86-755-8888-8888',
                'email' => 'shenzhen.warehouse@neogiga.com',
                'manager' => 'Li Wei',
                'assistant_manager' => 'Zhang Min',
                'wechat' => 'NeoGigaShenzhen',
            ]),
            'operating_hours' => json_encode([
                'monday' => ['08:00', '20:00'],
                'tuesday' => ['08:00', '20:00'],
                'wednesday' => ['08:00', '20:00'],
                'thursday' => ['08:00', '20:00'],
                'friday' => ['08:00', '20:00'],
                'saturday' => ['09:00', '17:00'],
                'sunday' => 'closed',
            ]),
            'capacity_units' => 100000,
            'current_stock_units' => 0,
            'is_distribution_center' => true,
            'is_fulfillment_center' => true,
            'allows_cross_border' => true,
        ]);

        // Create Delhi, India - Regional Distribution Center (South Asia)
        Warehouse::create([
            'id' => (string) Str::uuid(),
            'name' => 'NeoGiga Delhi Regional Distribution Center',
            'code' => 'IN-RDC-DEL-001',
            'region' => 'South Asia',
            'country' => 'India',
            'city' => 'New Delhi',
            'address' => 'Manesar Industrial Area, Gurugram, Haryana',
            'postal_code' => '122050',
            'latitude' => 28.3670,
            'longitude' => 76.9072,
            'timezone' => 'Asia/Kolkata',
            'currency_code' => 'INR',
            'status' => 'active',
            'contact_info' => json_encode([
                'phone' => '+91-124-444-4444',
                'email' => 'delhi.warehouse@neogiga.com',
                'manager' => 'Priya Patel',
                'assistant_manager' => 'Amit Sharma',
            ]),
            'operating_hours' => json_encode([
                'monday' => ['08:30', '19:30'],
                'tuesday' => ['08:30', '19:30'],
                'wednesday' => ['08:30', '19:30'],
                'thursday' => ['08:30', '19:30'],
                'friday' => ['08:30', '19:30'],
                'saturday' => ['09:00', '17:00'],
                'sunday' => 'closed',
            ]),
            'capacity_units' => 75000,
            'current_stock_units' => 0,
            'is_distribution_center' => true,
            'is_fulfillment_center' => true,
            'allows_cross_border' => true,
        ]);

        // Create Kathmandu, Nepal - Main Distribution Center (Nepal HQ)
        Warehouse::create([
            'id' => (string) Str::uuid(),
            'name' => 'NeoGiga Kathmandu Main Distribution Center',
            'code' => 'NP-MDC-KTM-001',
            'region' => 'South Asia',
            'country' => 'Nepal',
            'city' => 'Kathmandu',
            'address' => 'Birtamod Industrial Corridor, Jhapa District',
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
            'capacity_units' => 50000,
            'current_stock_units' => 0,
            'is_distribution_center' => true,
            'is_fulfillment_center' => true,
            'allows_cross_border' => true,
        ]);

        // Create Colombo, Sri Lanka - Regional Distribution Center (South Asia)
        Warehouse::create([
            'id' => (string) Str::uuid(),
            'name' => 'NeoGiga Colombo Regional Distribution Center',
            'code' => 'LK-RDC-CMB-001',
            'region' => 'South Asia',
            'country' => 'Sri Lanka',
            'city' => 'Colombo',
            'address' => 'Biyanipura Export Processing Zone, Colombo',
            'postal_code' => '10600',
            'latitude' => 6.9271,
            'longitude' => 79.8612,
            'timezone' => 'Asia/Colombo',
            'currency_code' => 'LKR',
            'status' => 'active',
            'contact_info' => json_encode([
                'phone' => '+94-11-222-2222',
                'email' => 'colombo.warehouse@neogiga.com',
                'manager' => 'Dilshan Perera',
                'assistant_manager' => 'Nishani Fernando',
            ]),
            'operating_hours' => json_encode([
                'monday' => ['08:30', '18:30'],
                'tuesday' => ['08:30', '18:30'],
                'wednesday' => ['08:30', '18:30'],
                'thursday' => ['08:30', '18:30'],
                'friday' => ['08:30', '18:30'],
                'saturday' => ['09:00', '15:00'],
                'sunday' => 'closed',
            ]),
            'capacity_units' => 40000,
            'current_stock_units' => 0,
            'is_distribution_center' => true,
            'is_fulfillment_center' => true,
            'allows_cross_border' => true,
        ]);
    }
}
