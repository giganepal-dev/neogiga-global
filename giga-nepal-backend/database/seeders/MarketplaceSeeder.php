<?php

namespace Database\Seeders;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceDomain;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\ProductBrand;
use App\Models\Marketplace\Warehouse;
use Illuminate\Database\Seeder;

class MarketplaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Countries
        $this->seedCountries();
        
        // Seed Currencies
        $this->seedCurrencies();
        
        // Seed Marketplaces
        $this->seedMarketplaces();
        
        // Seed Categories
        $this->seedCategories();
        
        // Seed Brands
        $this->seedBrands();
        
        // Seed Warehouses
        $this->seedWarehouses();
    }

    protected function seedCountries(): void
    {
        $countries = [
            ['code' => 'GLOBAL', 'name' => 'Global', 'iso_code' => null, 'phone_code' => null],
            ['code' => 'NP', 'name' => 'Nepal', 'iso_code' => 'NP', 'phone_code' => '+977'],
            ['code' => 'IN', 'name' => 'India', 'iso_code' => 'IN', 'phone_code' => '+91'],
            ['code' => 'US', 'name' => 'United States', 'iso_code' => 'US', 'phone_code' => '+1'],
            ['code' => 'GB', 'name' => 'United Kingdom', 'iso_code' => 'GB', 'phone_code' => '+44'],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(['code' => $country['code']], $country);
        }
    }

    protected function seedCurrencies(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2],
            ['code' => 'NPR', 'name' => 'Nepalese Rupee', 'symbol' => 'Rs.', 'decimal_places' => 2],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'decimal_places' => 2],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(['code' => $currency['code']], $currency);
        }
    }

    protected function seedMarketplaces(): void
    {
        $global = Marketplace::updateOrCreate(
            ['code' => 'global'],
            [
                'name' => 'NeoGiga Global',
                'description' => 'Global master marketplace for electronics, robotics, and industrial components',
                'country_id' => Country::where('code', 'GLOBAL')->first()?->id,
                'currency_id' => Currency::where('code', 'USD')->first()?->id,
                'is_active' => true,
                'is_master' => true,
            ]
        );

        MarketplaceDomain::updateOrCreate(
            ['domain' => 'neogiga.com'],
            ['marketplace_id' => $global->id, 'is_primary' => true]
        );

        $nepal = Marketplace::updateOrCreate(
            ['code' => 'nepal'],
            [
                'name' => 'GigaNepal',
                'description' => 'Nepal regional marketplace',
                'country_id' => Country::where('code', 'NP')->first()?->id,
                'currency_id' => Currency::where('code', 'NPR')->first()?->id,
                'is_active' => true,
                'is_master' => false,
            ]
        );

        MarketplaceDomain::updateOrCreate(
            ['domain' => 'giganepal.com'],
            ['marketplace_id' => $nepal->id, 'is_primary' => true]
        );

        $india = Marketplace::updateOrCreate(
            ['code' => 'india'],
            [
                'name' => 'NeoGiga India',
                'description' => 'India regional marketplace',
                'country_id' => Country::where('code', 'IN')->first()?->id,
                'currency_id' => Currency::where('code', 'INR')->first()?->id,
                'is_active' => true,
                'is_master' => false,
            ]
        );

        MarketplaceDomain::updateOrCreate(
            ['domain' => 'neogiga.in'],
            ['marketplace_id' => $india->id, 'is_primary' => true]
        );
    }

    protected function seedCategories(): void
    {
        $categories = [
            [
                'name' => 'Electronics Components',
                'slug' => 'electronics-components',
                'children' => [
                    ['name' => 'ICs', 'slug' => 'ics'],
                    ['name' => 'Microcontrollers', 'slug' => 'microcontrollers'],
                    ['name' => 'Development Boards', 'slug' => 'development-boards'],
                    ['name' => 'Arduino', 'slug' => 'arduino'],
                    ['name' => 'ESP32', 'slug' => 'esp32'],
                    ['name' => 'Raspberry Pi', 'slug' => 'raspberry-pi'],
                    ['name' => 'Sensors', 'slug' => 'sensors'],
                    ['name' => 'Modules', 'slug' => 'modules'],
                    ['name' => 'Resistors', 'slug' => 'resistors'],
                    ['name' => 'Capacitors', 'slug' => 'capacitors'],
                    ['name' => 'Diodes', 'slug' => 'diodes'],
                    ['name' => 'Transistors', 'slug' => 'transistors'],
                    ['name' => 'Relays', 'slug' => 'relays'],
                    ['name' => 'Connectors', 'slug' => 'connectors'],
                    ['name' => 'Switches', 'slug' => 'switches'],
                    ['name' => 'Cables', 'slug' => 'cables'],
                    ['name' => 'Breadboards', 'slug' => 'breadboards'],
                    ['name' => 'PCB and Prototyping', 'slug' => 'pcb-prototyping'],
                ]
            ],
            [
                'name' => 'Robotics',
                'slug' => 'robotics',
                'children' => [
                    ['name' => 'Robot Kits', 'slug' => 'robot-kits'],
                    ['name' => 'Robot Chassis', 'slug' => 'robot-chassis'],
                    ['name' => 'Robot Motors', 'slug' => 'robot-motors'],
                    ['name' => 'Servo Motors', 'slug' => 'servo-motors'],
                    ['name' => 'Stepper Motors', 'slug' => 'stepper-motors'],
                    ['name' => 'Motor Drivers', 'slug' => 'motor-drivers'],
                    ['name' => 'Wheels and Tracks', 'slug' => 'wheels-tracks'],
                    ['name' => 'Robotic Arms', 'slug' => 'robotic-arms'],
                    ['name' => 'Drone Components', 'slug' => 'drone-components'],
                    ['name' => 'Actuators', 'slug' => 'actuators'],
                    ['name' => 'Robot Sensors', 'slug' => 'robot-sensors'],
                    ['name' => 'Educational Robots', 'slug' => 'educational-robots'],
                ]
            ],
            [
                'name' => 'Batteries and Power Storage',
                'slug' => 'batteries-power-storage',
                'children' => [
                    ['name' => 'Li-ion Cells', 'slug' => 'li-ion-cells'],
                    ['name' => 'LiFePO4 Batteries', 'slug' => 'lifepo4-batteries'],
                    ['name' => 'BMS', 'slug' => 'bms'],
                    ['name' => 'Battery Packs', 'slug' => 'battery-packs'],
                    ['name' => 'Battery Chargers', 'slug' => 'battery-chargers'],
                    ['name' => 'Power Banks', 'slug' => 'power-banks'],
                    ['name' => 'UPS Batteries', 'slug' => 'ups-batteries'],
                    ['name' => 'Solar Batteries', 'slug' => 'solar-batteries'],
                    ['name' => 'EV Battery Modules', 'slug' => 'ev-battery-modules'],
                    ['name' => 'Battery Testers', 'slug' => 'battery-testers'],
                    ['name' => 'Battery Holders', 'slug' => 'battery-holders'],
                    ['name' => 'Battery Protection Boards', 'slug' => 'battery-protection-boards'],
                ]
            ],
            [
                'name' => 'Solar and Renewable Energy',
                'slug' => 'solar-renewable-energy',
                'children' => [
                    ['name' => 'Solar Panels', 'slug' => 'solar-panels'],
                    ['name' => 'Solar Charge Controllers', 'slug' => 'solar-charge-controllers'],
                    ['name' => 'Inverters', 'slug' => 'inverters'],
                    ['name' => 'Hybrid Inverters', 'slug' => 'hybrid-inverters'],
                    ['name' => 'Micro Inverters', 'slug' => 'micro-inverters'],
                    ['name' => 'Solar Cables', 'slug' => 'solar-cables'],
                    ['name' => 'Mounting Structures', 'slug' => 'mounting-structures'],
                    ['name' => 'DC Breakers', 'slug' => 'dc-breakers'],
                    ['name' => 'Energy Meters', 'slug' => 'energy-meters'],
                    ['name' => 'Solar Kits', 'slug' => 'solar-kits'],
                ]
            ],
            [
                'name' => 'Tools and Equipment',
                'slug' => 'tools-equipment',
                'children' => [
                    ['name' => 'Hand Tools', 'slug' => 'hand-tools'],
                    ['name' => 'Power Tools', 'slug' => 'power-tools'],
                    ['name' => 'Soldering Tools', 'slug' => 'soldering-tools'],
                    ['name' => 'Testing Instruments', 'slug' => 'testing-instruments'],
                    ['name' => 'Multimeters', 'slug' => 'multimeters'],
                    ['name' => 'Oscilloscopes', 'slug' => 'oscilloscopes'],
                    ['name' => 'Clamp Meters', 'slug' => 'clamp-meters'],
                    ['name' => 'Lab Power Supplies', 'slug' => 'lab-power-supplies'],
                    ['name' => 'Crimping Tools', 'slug' => 'crimping-tools'],
                    ['name' => '3D Printers', 'slug' => '3d-printers'],
                    ['name' => 'CNC Tools', 'slug' => 'cnc-tools'],
                    ['name' => 'Laser Cutters', 'slug' => 'laser-cutters'],
                ]
            ],
            [
                'name' => 'Industrial Automation',
                'slug' => 'industrial-automation',
                'children' => [
                    ['name' => 'PLC', 'slug' => 'plc'],
                    ['name' => 'HMI', 'slug' => 'hmi'],
                    ['name' => 'VFD', 'slug' => 'vfd'],
                    ['name' => 'Sensors', 'slug' => 'industrial-sensors'],
                    ['name' => 'Encoders', 'slug' => 'encoders'],
                    ['name' => 'Switchgear', 'slug' => 'switchgear'],
                    ['name' => 'Industrial Relays', 'slug' => 'industrial-relays'],
                    ['name' => 'Control Panels', 'slug' => 'control-panels'],
                    ['name' => 'Pneumatic Components', 'slug' => 'pneumatic-components'],
                    ['name' => 'Industrial Cables', 'slug' => 'industrial-cables'],
                ]
            ],
            [
                'name' => 'EV and Mobility Components',
                'slug' => 'ev-mobility-components',
                'children' => [
                    ['name' => 'EV Chargers', 'slug' => 'ev-chargers'],
                    ['name' => 'Motor Controllers', 'slug' => 'motor-controllers'],
                    ['name' => 'Hub Motors', 'slug' => 'hub-motors'],
                    ['name' => 'BLDC Motors', 'slug' => 'bldc-motors'],
                    ['name' => 'DC-DC Converters', 'slug' => 'dc-dc-converters'],
                    ['name' => 'Battery Packs', 'slug' => 'ev-battery-packs'],
                    ['name' => 'BMS', 'slug' => 'ev-bms'],
                    ['name' => 'Wiring Harness', 'slug' => 'wiring-harness'],
                    ['name' => 'Connectors', 'slug' => 'ev-connectors'],
                    ['name' => 'Vehicle Sensors', 'slug' => 'vehicle-sensors'],
                ]
            ],
            [
                'name' => 'Raw Materials and Fabrication',
                'slug' => 'raw-materials-fabrication',
                'children' => [
                    ['name' => 'Acrylic Sheets', 'slug' => 'acrylic-sheets'],
                    ['name' => 'Aluminum Profiles', 'slug' => 'aluminum-profiles'],
                    ['name' => 'Steel Parts', 'slug' => 'steel-parts'],
                    ['name' => 'Fasteners', 'slug' => 'fasteners'],
                    ['name' => 'Screws', 'slug' => 'screws'],
                    ['name' => 'Nuts and Bolts', 'slug' => 'nuts-bolts'],
                    ['name' => 'Magnets', 'slug' => 'magnets'],
                    ['name' => 'Bearings', 'slug' => 'bearings'],
                    ['name' => 'Rubber Parts', 'slug' => 'rubber-parts'],
                    ['name' => '3D Printing Filaments', 'slug' => '3d-printing-filaments'],
                    ['name' => 'Copper Wire', 'slug' => 'copper-wire'],
                    ['name' => 'Heat Shrink Tubes', 'slug' => 'heat-shrink-tubes'],
                ]
            ],
            [
                'name' => 'DIY and Maker Kits',
                'slug' => 'diy-maker-kits',
                'children' => [
                    ['name' => 'STEM Kits', 'slug' => 'stem-kits'],
                    ['name' => 'School Lab Kits', 'slug' => 'school-lab-kits'],
                    ['name' => 'IoT Kits', 'slug' => 'iot-kits'],
                    ['name' => 'Robotics Kits', 'slug' => 'robotics-kits'],
                    ['name' => 'Solar Kits', 'slug' => 'diy-solar-kits'],
                    ['name' => 'Smart Home Kits', 'slug' => 'smart-home-kits'],
                    ['name' => 'Agriculture Automation Kits', 'slug' => 'agriculture-automation-kits'],
                    ['name' => 'Weather Station Kits', 'slug' => 'weather-station-kits'],
                    ['name' => 'Security System Kits', 'slug' => 'security-system-kits'],
                ]
            ],
            [
                'name' => 'Computer and Networking',
                'slug' => 'computer-networking',
                'children' => [
                    ['name' => 'Routers', 'slug' => 'routers'],
                    ['name' => 'Switches', 'slug' => 'network-switches'],
                    ['name' => 'Networking Cables', 'slug' => 'networking-cables'],
                    ['name' => 'Fiber Equipment', 'slug' => 'fiber-equipment'],
                    ['name' => 'Server Accessories', 'slug' => 'server-accessories'],
                    ['name' => 'Storage', 'slug' => 'storage'],
                    ['name' => 'CCTV', 'slug' => 'cctv'],
                    ['name' => 'Access Control', 'slug' => 'access-control'],
                    ['name' => 'Biometric Devices', 'slug' => 'biometric-devices'],
                ]
            ],
            [
                'name' => 'Safety and Security',
                'slug' => 'safety-security',
                'children' => [
                    ['name' => 'CCTV Cameras', 'slug' => 'cctv-cameras'],
                    ['name' => 'NVR/DVR', 'slug' => 'nvr-dvr'],
                    ['name' => 'Access Control', 'slug' => 'security-access-control'],
                    ['name' => 'RFID', 'slug' => 'rfid'],
                    ['name' => 'GPS Trackers', 'slug' => 'gps-trackers'],
                    ['name' => 'Alarm Systems', 'slug' => 'alarm-systems'],
                    ['name' => 'Fire Safety Devices', 'slug' => 'fire-safety-devices'],
                    ['name' => 'Personal Protective Equipment', 'slug' => 'ppe'],
                ]
            ],
            [
                'name' => 'Agriculture and Smart Farming',
                'slug' => 'agriculture-smart-farming',
                'children' => [
                    ['name' => 'Soil Sensors', 'slug' => 'soil-sensors'],
                    ['name' => 'Moisture Sensors', 'slug' => 'moisture-sensors'],
                    ['name' => 'Water Pumps', 'slug' => 'water-pumps'],
                    ['name' => 'Irrigation Controllers', 'slug' => 'irrigation-controllers'],
                    ['name' => 'Greenhouse Automation', 'slug' => 'greenhouse-automation'],
                    ['name' => 'Weather Stations', 'slug' => 'farming-weather-stations'],
                    ['name' => 'Fertilizer Dosing Systems', 'slug' => 'fertilizer-dosing-systems'],
                    ['name' => 'Farm Drones', 'slug' => 'farm-drones'],
                ]
            ],
        ];

        foreach ($categories as $categoryData) {
            $parent = ProductCategory::updateOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    'name' => $categoryData['name'],
                    'parent_id' => null,
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );

            if (isset($categoryData['children'])) {
                foreach ($categoryData['children'] as $index => $child) {
                    ProductCategory::updateOrCreate(
                        ['slug' => $child['slug']],
                        [
                            'name' => $child['name'],
                            'parent_id' => $parent->id,
                            'is_active' => true,
                            'sort_order' => $index,
                        ]
                    );
                }
            }
        }
    }

    protected function seedBrands(): void
    {
        $brands = [
            ['name' => 'Arduino', 'slug' => 'arduino', 'website' => 'https://www.arduino.cc'],
            ['name' => 'Espressif', 'slug' => 'espressif', 'website' => 'https://www.espressif.com'],
            ['name' => 'Raspberry Pi', 'slug' => 'raspberry-pi', 'website' => 'https://www.raspberrypi.org'],
            ['name' => 'Waveshare', 'slug' => 'waveshare', 'website' => 'https://www.waveshare.com'],
            ['name' => 'Keyestudio', 'slug' => 'keyestudio', 'website' => 'https://www.keyestudio.com'],
            ['name' => 'DFRobot', 'slug' => 'dfrobot', 'website' => 'https://www.dfrobot.com'],
            ['name' => 'Mean Well', 'slug' => 'mean-well', 'website' => 'https://www.meanwell.com'],
            ['name' => 'Deye', 'slug' => 'deye', 'website' => 'https://www.deye.com'],
            ['name' => 'Schneider Electric', 'slug' => 'schneider-electric', 'website' => 'https://www.se.com'],
        ];

        foreach ($brands as $brand) {
            ProductBrand::updateOrCreate(['slug' => $brand['slug']], $brand);
        }
    }

    protected function seedWarehouses(): void
    {
        $marketplaces = Marketplace::all();
        
        foreach ($marketplaces as $marketplace) {
            Warehouse::updateOrCreate(
                ['code' => strtoupper($marketplace->code) . '-MAIN'],
                [
                    'name' => $marketplace->name . ' Main Warehouse',
                    'marketplace_id' => $marketplace->id,
                    'type' => 'main',
                    'is_active' => true,
                    'address_line1' => 'Main Distribution Center',
                    'country_id' => $marketplace->country_id,
                ]
            );
        }
    }
}
