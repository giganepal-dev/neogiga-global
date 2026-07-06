<?php

namespace Database\Seeders\ProductSeeders;

use Illuminate\Database\Seeder;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\ProductBrand;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Main Categories
        $mainCategories = [
            ['name' => 'Electronics Components', 'slug' => 'electronics-components', 'icon' => 'chip'],
            ['name' => 'Robotics', 'slug' => 'robotics', 'icon' => 'robot'],
            ['name' => 'Batteries and Power Storage', 'slug' => 'batteries-power-storage', 'icon' => 'battery'],
            ['name' => 'Solar and Renewable Energy', 'slug' => 'solar-renewable-energy', 'icon' => 'sun'],
            ['name' => 'Tools and Equipment', 'slug' => 'tools-equipment', 'icon' => 'tools'],
            ['name' => 'Industrial Automation', 'slug' => 'industrial-automation', 'icon' => 'factory'],
            ['name' => 'EV and Mobility Components', 'slug' => 'ev-mobility-components', 'icon' => 'car'],
            ['name' => 'Raw Materials and Fabrication', 'slug' => 'raw-materials-fabrication', 'icon' => 'box'],
            ['name' => 'DIY and Maker Kits', 'slug' => 'diy-maker-kits', 'icon' => 'puzzle'],
            ['name' => 'Computer and Networking', 'slug' => 'computer-networking', 'icon' => 'network'],
            ['name' => 'Safety and Security', 'slug' => 'safety-security', 'icon' => 'shield'],
            ['name' => 'Agriculture and Smart Farming', 'slug' => 'agriculture-smart-farming', 'icon' => 'leaf'],
        ];

        $categoryMap = [];

        foreach ($mainCategories as $catData) {
            $category = ProductCategory::firstOrCreate(
                ['slug' => $catData['slug']],
                [
                    'name' => $catData['name'],
                    'parent_id' => null,
                    'description' => "Browse our wide selection of {$catData['name']}",
                    'image_url' => null,
                    'icon' => $catData['icon'],
                    'sort_order' => array_search($catData['name'], array_column($mainCategories, 'name')),
                    'is_active' => true,
                    'show_in_menu' => true,
                    'meta_title' => $catData['name'] . ' - NeoGiga Marketplace',
                    'meta_description' => "Find high-quality {$catData['name']} at NeoGiga",
                    'meta_keywords' => strtolower($catData['name']) . ', electronics, robotics, IoT',
                ]
            );
            $categoryMap[$catData['slug']] = $category->id;
        }

        // Subcategories for Electronics Components
        $electronicsSubcats = [
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
        ];

        foreach ($electronicsSubcats as $subcat) {
            ProductCategory::firstOrCreate(
                ['slug' => $subcat['slug']],
                [
                    'name' => $subcat['name'],
                    'parent_id' => $categoryMap['electronics-components'],
                    'description' => "Quality {$subcat['name']} for your projects",
                    'icon' => 'chip',
                    'is_active' => true,
                    'show_in_menu' => true,
                ]
            );
        }

        // Subcategories for Robotics
        $roboticsSubcats = [
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
        ];

        foreach ($roboticsSubcats as $subcat) {
            ProductCategory::firstOrCreate(
                ['slug' => $subcat['slug']],
                [
                    'name' => $subcat['name'],
                    'parent_id' => $categoryMap['robotics'],
                    'description' => "Build amazing robots with our {$subcat['name']}",
                    'icon' => 'robot',
                    'is_active' => true,
                    'show_in_menu' => true,
                ]
            );
        }

        // Subcategories for Batteries and Power Storage
        $batterySubcats = [
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
        ];

        foreach ($batterySubcats as $subcat) {
            ProductCategory::firstOrCreate(
                ['slug' => $subcat['slug']],
                [
                    'name' => $subcat['name'],
                    'parent_id' => $categoryMap['batteries-power-storage'],
                    'description' => "Reliable {$subcat['name']} for your power needs",
                    'icon' => 'battery',
                    'is_active' => true,
                    'show_in_menu' => true,
                ]
            );
        }

        // Seed Brands
        $brands = [
            ['name' => 'Arduino', 'slug' => 'arduino', 'website' => 'https://www.arduino.cc', 'country' => 'Italy'],
            ['name' => 'Espressif', 'slug' => 'espressif', 'website' => 'https://www.espressif.com', 'country' => 'China'],
            ['name' => 'Raspberry Pi', 'slug' => 'raspberry-pi', 'website' => 'https://www.raspberrypi.org', 'country' => 'UK'],
            ['name' => 'Waveshare', 'slug' => 'waveshare', 'website' => 'https://www.waveshare.com', 'country' => 'China'],
            ['name' => 'Keyestudio', 'slug' => 'keyestudio', 'website' => 'https://www.keyestudio.com', 'country' => 'China'],
            ['name' => 'DFRobot', 'slug' => 'dfrobot', 'website' => 'https://www.dfrobot.com', 'country' => 'China'],
            ['name' => 'Mean Well', 'slug' => 'mean-well', 'website' => 'https://www.meanwell.com', 'country' => 'Taiwan'],
            ['name' => 'Deye', 'slug' => 'deye', 'website' => 'https://www.deyeinverter.com', 'country' => 'China'],
            ['name' => 'Schneider Electric', 'slug' => 'schneider-electric', 'website' => 'https://www.se.com', 'country' => 'France'],
            ['name' => 'Texas Instruments', 'slug' => 'texas-instruments', 'website' => 'https://www.ti.com', 'country' => 'USA'],
            ['name' => 'STMicroelectronics', 'slug' => 'stmicroelectronics', 'website' => 'https://www.st.com', 'country' => 'Switzerland'],
            ['name' => 'Adafruit', 'slug' => 'adafruit', 'website' => 'https://www.adafruit.com', 'country' => 'USA'],
            ['name' => 'SparkFun', 'slug' => 'sparkfun', 'website' => 'https://www.sparkfun.com', 'country' => 'USA'],
            ['name' => 'Seeed Studio', 'slug' => 'seeed-studio', 'website' => 'https://www.seeedstudio.com', 'country' => 'China'],
            ['name' => 'Bosch', 'slug' => 'bosch', 'website' => 'https://www.bosch.com', 'country' => 'Germany'],
        ];

        foreach ($brands as $brandData) {
            ProductBrand::firstOrCreate(
                ['slug' => $brandData['slug']],
                [
                    'name' => $brandData['name'],
                    'website' => $brandData['website'],
                    'country' => $brandData['country'],
                    'logo_url' => null,
                    'description' => "{$brandData['name']} - Quality electronics and components",
                    'is_active' => true,
                    'meta_title' => "{$brandData['name']} Products - NeoGiga",
                    'meta_description' => "Shop authentic {$brandData['name']} products at NeoGiga",
                    'meta_keywords' => strtolower($brandData['name']) . ', electronics, components',
                ]
            );
        }

        $this->command->info('Product categories and brands seeded successfully!');
    }
}
