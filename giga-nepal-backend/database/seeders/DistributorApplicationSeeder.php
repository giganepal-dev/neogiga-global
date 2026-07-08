<?php

namespace Database\Seeders;

use App\Models\DistributorApplication;
use Illuminate\Database\Seeder;

class DistributorApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $applications = [
            [
                'full_name' => 'Rajesh Sharma',
                'email' => 'rajesh@himalayanelectronics.com.np',
                'phone' => '+977-9841234567',
                'company_name' => 'Himalayan Electronics Pvt Ltd',
                'company_registration_number' => 'REG-2020-123456',
                'pan_number' => 'PAN-987654321',
                'business_type' => 'company',
                'business_description' => 'Distributing consumer electronics in Nepal for over 10 years. Strong network with retailers across Kathmandu valley.',
                'years_in_business' => 10,
                'annual_revenue' => 5000000.00,
                'employee_count' => 25,
                'country_id' => 1, // Nepal
                'province_id' => 1,
                'district_id' => 1,
                'city' => 'Kathmandu',
                'coverage_areas' => json_encode(['Kathmandu', 'Lalitpur', 'Bhaktapur']),
                'exclusive_territory_requested' => true,
                'interested_categories' => json_encode([1, 2, 3, 5]), // Electronics, Robotics, IoT, Tools
                'has_warehouse' => true,
                'warehouse_count' => 1,
                'warehouse_sqft' => 3000,
                'has_sales_team' => true,
                'sales_team_size' => 10,
                'has_service_center' => true,
                'bank_name' => 'Nabil Bank',
                'bank_account_number' => '1234567890123456',
                'brand_references' => 'Sony Nepal, Samsung, LG',
                'initial_investment_capacity' => 2000000.00,
                'monthly_purchase_capacity' => 500000.00,
                'why_neogiga' => 'Looking for diverse product catalog and competitive pricing',
                'status' => 'pending',
                'ip_address' => '103.10.28.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'submitted_at' => now()->subDays(2),
                'created_at' => now()->subDays(2),
            ],
            [
                'full_name' => 'Priya Patel',
                'email' => 'priya@techvision.in',
                'phone' => '+91-9876543210',
                'company_name' => 'TechVision India Pvt Ltd',
                'company_registration_number' => 'CIN-U51909DL2019PTC123456',
                'pan_number' => 'ABCDE1234F',
                'business_type' => 'company',
                'business_description' => 'Leading distributor of industrial automation and IoT solutions in North India. Partnered with Siemens, Schneider, and Bosch.',
                'years_in_business' => 5,
                'annual_revenue' => 25000000.00,
                'employee_count' => 50,
                'country_id' => 2, // India
                'city' => 'New Delhi',
                'coverage_areas' => json_encode(['Delhi NCR', 'North India']),
                'exclusive_territory_requested' => false,
                'interested_categories' => json_encode([2, 3, 7, 9]), // Robotics, IoT, Industrial Automation, EV Components
                'has_warehouse' => true,
                'warehouse_count' => 2,
                'warehouse_sqft' => 8000,
                'has_sales_team' => true,
                'sales_team_size' => 20,
                'has_service_center' => true,
                'logistics_capability' => 'Own fleet of 5 delivery vehicles',
                'bank_name' => 'HDFC Bank',
                'bank_account_number' => '50200012345678',
                'brand_references' => 'Siemens India, Schneider Electric, Bosch',
                'initial_investment_capacity' => 10000000.00,
                'monthly_purchase_capacity' => 3000000.00,
                'why_neogiga' => 'Expanding distribution network for industrial products',
                'status' => 'under_review',
                'reviewed_by' => 1, // Admin user
                'reviewed_at' => now()->subDay(),
                'review_notes' => 'Strong candidate. Verify references and financial documents.',
                'ip_address' => '103.25.100.50',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'submitted_at' => now()->subDays(5),
                'created_at' => now()->subDays(5),
            ],
            [
                'full_name' => 'Mohammad Rahman',
                'email' => 'rahman@greenenergy.bd',
                'phone' => '+880-1712345678',
                'company_name' => 'Green Energy Solutions BD',
                'company_registration_number' => 'CRL-123456/2021',
                'pan_number' => 'TIN-123456789012',
                'business_type' => 'company',
                'business_description' => 'Specialized in solar energy systems and batteries. Installed 500+ solar projects across Bangladesh.',
                'years_in_business' => 7,
                'annual_revenue' => 15000000.00,
                'employee_count' => 35,
                'country_id' => 3, // Bangladesh
                'city' => 'Dhaka',
                'coverage_areas' => json_encode(['Dhaka', 'Chittagong']),
                'exclusive_territory_requested' => true,
                'interested_categories' => json_encode([6, 8, 10]), // Solar, Batteries, Smart Farming
                'has_warehouse' => true,
                'warehouse_count' => 2,
                'warehouse_sqft' => 5000,
                'has_sales_team' => true,
                'sales_team_size' => 15,
                'has_service_center' => true,
                'logistics_capability' => 'Partnership with local logistics providers',
                'bank_name' => 'BRAC Bank',
                'bank_account_number' => '1234567890',
                'brand_references' => 'Solarsium, Luminous, Exide',
                'initial_investment_capacity' => 5000000.00,
                'monthly_purchase_capacity' => 1500000.00,
                'why_neogiga' => 'Perfect platform for solar and battery distribution',
                'status' => 'approved',
                'reviewed_by' => 1,
                'reviewed_at' => now()->subDays(3),
                'approved_at' => now()->subDays(2),
                'review_notes' => 'Excellent track record in solar sector. Approved for exclusive territory.',
                'commission_rate' => 12.00,
                'payment_terms_days' => 30,
                'credit_limit' => 2000000.00,
                'is_active' => true,
                'ip_address' => '103.92.80.25',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64)',
                'submitted_at' => now()->subDays(10),
                'created_at' => now()->subDays(10),
            ],
            [
                'full_name' => 'Saman Perera',
                'email' => 'saman@lankatech.lk',
                'phone' => '+94-771234567',
                'company_name' => 'Lanka Tech Distributors',
                'company_registration_number' => 'PV202212345',
                'pan_number' => 'VAT-123456789',
                'business_type' => 'partnership',
                'business_description' => 'Consumer electronics and home appliances distribution. Strong retail network.',
                'years_in_business' => 3,
                'annual_revenue' => 8000000.00,
                'employee_count' => 12,
                'country_id' => 4, // Sri Lanka
                'city' => 'Colombo',
                'coverage_areas' => json_encode(['Western Province', 'Central Province']),
                'exclusive_territory_requested' => false,
                'interested_categories' => json_encode([1, 4, 5]), // Electronics, Batteries, Tools
                'has_warehouse' => false,
                'has_sales_team' => true,
                'sales_team_size' => 5,
                'has_service_center' => false,
                'bank_name' => 'Commercial Bank of Ceylon',
                'bank_account_number' => '1234567890123',
                'brand_references' => 'Abans, Singer, Softlogic',
                'initial_investment_capacity' => 2000000.00,
                'monthly_purchase_capacity' => 500000.00,
                'why_neogiga' => 'Want to expand product range',
                'status' => 'rejected',
                'reviewed_by' => 1,
                'reviewed_at' => now()->subDays(4),
                'rejection_reason' => 'Limited experience with B2B distribution. Annual revenue below minimum requirement for exclusive territory.',
                'review_notes' => 'Rejected for exclusive territory. May consider for non-exclusive after business grows.',
                'ip_address' => '112.134.200.10',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X)',
                'submitted_at' => now()->subDays(14),
                'created_at' => now()->subDays(14),
            ],
        ];

        foreach ($applications as $application) {
            DistributorApplication::create($application);
        }

        $this->command->info('Seeded 4 distributor applications from Nepal, India, Bangladesh, and Sri Lanka.');
    }
}
