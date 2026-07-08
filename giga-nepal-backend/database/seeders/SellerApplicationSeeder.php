<?php

namespace Database\Seeders;

use App\Models\SellerApplication;
use App\Models\User;
use Illuminate\Database\Seeder;

class SellerApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a test user
        $testUser = User::where('email', 'test.seller@example.com')->first();
        
        if (!$testUser) {
            $testUser = User::factory()->create([
                'name' => 'Test Seller',
                'email' => 'test.seller@example.com',
            ]);
        }

        $applications = [
            [
                'user_id' => $testUser->id,
                'business_name' => 'Tech Electronics Nepal',
                'business_type' => 'Retailer',
                'contact_person' => 'Rajesh Kumar',
                'email' => 'rajesh@techelectronics.com.np',
                'phone' => '+977-9841234567',
                'country' => 'Nepal',
                'state' => 'Bagmati',
                'city' => 'Kathmandu',
                'business_address' => 'New Baneshwor, Kathmandu',
                'pan_number' => '123456789',
                'vat_number' => 'VAT123456',
                'company_registration_number' => 'CRN-12345',
                'website_url' => 'https://techelectronics.com.np',
                'product_categories' => ['Electronics', 'Mobile Accessories', 'Computers'],
                'brand_names' => ['Samsung', 'Apple', 'Dell'],
                'estimated_monthly_volume' => 500000,
                'additional_info' => 'We have been in electronics retail for 10 years with 3 stores in Kathmandu.',
                'status' => 'pending',
            ],
            [
                'user_id' => null,
                'business_name' => 'Mumbai Solar Solutions',
                'business_type' => 'Distributor',
                'contact_person' => 'Priya Sharma',
                'email' => 'priya@mumbaisolar.in',
                'phone' => '+91-9876543210',
                'country' => 'India',
                'state' => 'Maharashtra',
                'city' => 'Mumbai',
                'business_address' => 'Andheri East, Mumbai',
                'pan_number' => 'ABCDE1234F',
                'vat_number' => 'GST27ABCDE1234F1Z5',
                'company_registration_number' => 'U12345MH2020PTC123456',
                'website_url' => 'https://mumbaisolar.in',
                'product_categories' => ['Solar Panels', 'Inverters', 'Batteries', 'IoT Devices'],
                'brand_names' => ['Tata Solar', 'Adani Green', 'Luminous'],
                'estimated_monthly_volume' => 1200000,
                'additional_info' => 'Authorized distributor for major solar brands in Western India.',
                'status' => 'under_review',
            ],
            [
                'user_id' => null,
                'business_name' => 'Bangladesh Robotics Hub',
                'business_type' => 'Manufacturer',
                'contact_person' => 'Ahmed Hassan',
                'email' => 'ahmed@bdrobotics.bd',
                'phone' => '+880-1712345678',
                'country' => 'Bangladesh',
                'state' => 'Dhaka',
                'city' => 'Dhaka',
                'business_address' => 'Gulshan-1, Dhaka',
                'pan_number' => 'BD-TIN-123456',
                'company_registration_number' => 'C-12345/2019',
                'website_url' => 'https://bdrobotics.bd',
                'product_categories' => ['Robotics', 'Arduino', 'Sensors', 'Educational Kits'],
                'brand_names' => ['Own Brand'],
                'estimated_monthly_volume' => 300000,
                'additional_info' => 'We manufacture educational robotics kits for schools and universities.',
                'status' => 'approved',
                'admin_notes' => 'Verified manufacturer. Approved for educational products category.',
                'approved_at' => now()->subDays(5),
            ],
            [
                'user_id' => null,
                'business_name' => 'Colombo Industrial Supplies',
                'business_type' => 'Brand Owner',
                'contact_person' => 'Saman Perera',
                'email' => 'saman@colomboindustrial.lk',
                'phone' => '+94-771234567',
                'country' => 'Sri Lanka',
                'state' => 'Western',
                'city' => 'Colombo',
                'business_address' => 'Fort, Colombo 01',
                'company_registration_number' => 'PV-123456',
                'website_url' => null,
                'product_categories' => ['Industrial Automation', 'Tools', 'Safety Equipment'],
                'brand_names' => ['Bosch', 'Makita', '3M'],
                'estimated_monthly_volume' => 800000,
                'additional_info' => 'Exclusive brand partner for industrial tools in Sri Lanka.',
                'status' => 'rejected',
                'admin_notes' => 'Incomplete documentation. Missing tax certificate.',
            ],
        ];

        foreach ($applications as $app) {
            SellerApplication::create($app);
        }

        $this->command->info('Seller applications seeded successfully!');
    }
}
