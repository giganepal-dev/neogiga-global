<?php

namespace Database\Seeders\VendorSeeders;

use Illuminate\Database\Seeder;
use App\Models\Vendor\Vendor;
use App\Models\Vendor\VendorProfile;
use App\Models\Vendor\VendorMarketplaceApproval;
use App\Models\Inventory\Warehouse;
use App\Models\Product\Product;
use App\Models\Product\ProductVariant;
use App\Models\Product\ProductImage;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Currency;
use App\Models\Pricing\MarketplaceProductPrice;
use App\Models\Inventory\InventoryStock;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $globalMp = Marketplace::where('code', 'GLOBAL')->first();
        $nepalMp = Marketplace::where('code', 'NEPAL')->first();
        $indiaMp = Marketplace::where('code', 'INDIA')->first();

        // Seed Sample Vendors
        $vendors = [
            [
                'name' => 'TechComponents Nepal',
                'slug' => 'techcomponents-nepal',
                'email' => 'info@techcomponents.com.np',
                'phone' => '+977-1-4444444',
                'country' => 'NP',
                'type' => 'retailer',
                'description' => 'Leading electronics components supplier in Nepal',
            ],
            [
                'name' => 'RoboIndia Solutions',
                'slug' => 'roboindia-solutions',
                'email' => 'sales@roboindia.com',
                'phone' => '+91-80-12345678',
                'country' => 'IN',
                'type' => 'manufacturer',
                'description' => 'Robotics and automation components manufacturer',
            ],
            [
                'name' => 'Global Electronics Supply',
                'slug' => 'global-electronics-supply',
                'email' => 'contact@globalelectronics.com',
                'phone' => '+1-555-1234567',
                'country' => 'US',
                'type' => 'distributor',
                'description' => 'Global distributor of electronic components and tools',
            ],
        ];

        foreach ($vendors as $vendorData) {
            $vendor = Vendor::firstOrCreate(
                ['email' => $vendorData['email']],
                [
                    'name' => $vendorData['name'],
                    'slug' => $vendorData['slug'],
                    'phone' => $vendorData['phone'],
                    'country' => $vendorData['country'],
                    'type' => $vendorData['type'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Create Vendor Profile
            VendorProfile::firstOrCreate(
                ['vendor_id' => $vendor->id],
                [
                    'business_name' => $vendorData['name'],
                    'business_registration_number' => 'REG-' . strtoupper(str_replace('-', '', $vendorData['slug'])),
                    'tax_id' => 'TAX-' . rand(100000, 999999),
                    'address_line1' => 'Main Business Street',
                    'city' => $vendorData['country'] === 'NP' ? 'Kathmandu' : ($vendorData['country'] === 'IN' ? 'Bangalore' : 'New York'),
                    'postal_code' => $vendorData['country'] === 'NP' ? '44600' : ($vendorData['country'] === 'IN' ? '560001' : '10001'),
                    'website' => 'https://' . str_replace('.', '', strtolower($vendorData['name'])) . '.com',
                    'logo_url' => null,
                    'banner_url' => null,
                    'description' => $vendorData['description'],
                    'social_links' => json_encode(['facebook' => null, 'twitter' => null, 'linkedin' => null]),
                    'bank_name' => 'Sample Bank',
                    'bank_account_number' => '1234567890',
                    'bank_ifsc' => 'BANK0001234',
                    'payment_terms' => 'Net 30',
                    'shipping_policy' => 'Ships within 2-3 business days',
                    'return_policy' => '30-day return policy',
                ]
            );

            // Approve for relevant marketplaces
            if ($vendorData['country'] === 'NP') {
                VendorMarketplaceApproval::firstOrCreate(
                    ['vendor_id' => $vendor->id, 'marketplace_id' => $nepalMp?->id],
                    ['status' => 'approved', 'approved_by' => 1, 'notes' => 'Local vendor approved']
                );
                VendorMarketplaceApproval::firstOrCreate(
                    ['vendor_id' => $vendor->id, 'marketplace_id' => $globalMp?->id],
                    ['status' => 'approved', 'approved_by' => 1, 'notes' => 'Global marketplace approval']
                );
            } elseif ($vendorData['country'] === 'IN') {
                VendorMarketplaceApproval::firstOrCreate(
                    ['vendor_id' => $vendor->id, 'marketplace_id' => $indiaMp?->id],
                    ['status' => 'approved', 'approved_by' => 1, 'notes' => 'India vendor approved']
                );
                VendorMarketplaceApproval::firstOrCreate(
                    ['vendor_id' => $vendor->id, 'marketplace_id' => $globalMp?->id],
                    ['status' => 'approved', 'approved_by' => 1, 'notes' => 'Global marketplace approval']
                );
            } else {
                VendorMarketplaceApproval::firstOrCreate(
                    ['vendor_id' => $vendor->id, 'marketplace_id' => $globalMp?->id],
                    ['status' => 'approved', 'approved_by' => 1, 'notes' => 'Global vendor approved']
                );
            }

            // Create Warehouse for vendor
            $warehouse = Warehouse::firstOrCreate(
                ['code' => 'WH-' . strtoupper(substr($vendorData['slug'], 0, 5))],
                [
                    'name' => $vendorData['name'] . ' Main Warehouse',
                    'type' => 'vendor',
                    'vendor_id' => $vendor->id,
                    'address_line1' => 'Warehouse Street',
                    'city' => $vendorData['country'] === 'NP' ? 'Kathmandu' : ($vendorData['country'] === 'IN' ? 'Bangalore' : 'New York'),
                    'country' => $vendorData['country'],
                    'postal_code' => $vendorData['country'] === 'NP' ? '44600' : ($vendorData['country'] === 'IN' ? '560001' : '10001'),
                    'latitude' => $vendorData['country'] === 'NP' ? '27.7172' : ($vendorData['country'] === 'IN' ? '12.9716' : '40.7128'),
                    'longitude' => $vendorData['country'] === 'NP' ? '85.3240' : ($vendorData['country'] === 'IN' ? '77.5946' : '-74.0060'),
                    'is_active' => true,
                    'is_default' => true,
                ]
            );
        }

        // Seed Sample Products
        $this->seedSampleProducts($globalMp, $nepalMp, $indiaMp);

        $this->command->info('Vendor and product seeder completed successfully!');
    }

    private function seedSampleProducts(?Marketplace $globalMp, ?Marketplace $nepalMp, ?Marketplace $indiaMp): void
    {
        $products = [
            [
                'name' => 'ESP32 Development Board WiFi Bluetooth',
                'slug' => 'esp32-development-board-wifi-bluetooth',
                'sku' => 'ESP32-DEV-001',
                'description' => 'ESP32-WROOM-32 based development board with WiFi and Bluetooth capabilities. Perfect for IoT projects.',
                'short_description' => 'ESP32 dev board with WiFi & BT',
                'type' => 'simple',
                'category_slug' => 'esp32',
                'brand_slug' => 'espressif',
                'base_price' => 8.50,
                'currency' => 'USD',
                'weight' => 0.05,
                'dimensions' => '{"length": 5.5, "width": 2.8, "height": 1.2}',
                'stock_quantity' => 500,
                'low_stock_threshold' => 50,
                'manage_stock' => true,
                'in_stock' => true,
                'specifications' => json_encode([
                    'Microcontroller' => 'ESP32-WROOM-32',
                    'Flash Memory' => '4MB',
                    'SRAM' => '520KB',
                    'WiFi' => '802.11 b/g/n',
                    'Bluetooth' => 'v4.2 BR/EDR and BLE',
                    'GPIO Pins' => '34',
                    'ADC' => '18 x 12-bit',
                    'DAC' => '2 x 8-bit',
                    'Operating Voltage' => '3.3V',
                    'Input Voltage' => '5V via USB',
                ]),
            ],
            [
                'name' => 'Arduino Uno R3',
                'slug' => 'arduino-uno-r3',
                'sku' => 'ARD-UNO-R3',
                'description' => 'The original Arduino Uno R3 board based on ATmega328P microcontroller. Ideal for beginners and professionals alike.',
                'short_description' => 'Classic Arduino Uno R3 board',
                'type' => 'simple',
                'category_slug' => 'arduino',
                'brand_slug' => 'arduino',
                'base_price' => 25.00,
                'currency' => 'USD',
                'weight' => 0.08,
                'dimensions' => '{"length": 6.8, "width": 5.3, "height": 1.5}',
                'stock_quantity' => 300,
                'low_stock_threshold' => 30,
                'manage_stock' => true,
                'in_stock' => true,
                'specifications' => json_encode([
                    'Microcontroller' => 'ATmega328P',
                    'Operating Voltage' => '5V',
                    'Input Voltage' => '7-12V',
                    'Digital I/O Pins' => '14 (6 PWM)',
                    'Analog Input Pins' => '6',
                    'Flash Memory' => '32KB',
                    'SRAM' => '2KB',
                    'EEPROM' => '1KB',
                    'Clock Speed' => '16MHz',
                ]),
            ],
            [
                'name' => 'L298N Dual H-Bridge Motor Driver',
                'slug' => 'l298n-dual-h-bridge-motor-driver',
                'sku' => 'MTR-L298N',
                'description' => 'Dual H-Bridge motor driver module capable of controlling two DC motors or one stepper motor. Up to 2A per channel.',
                'short_description' => 'Dual H-bridge motor driver 2A',
                'type' => 'simple',
                'category_slug' => 'motor-drivers',
                'brand_slug' => null,
                'base_price' => 4.50,
                'currency' => 'USD',
                'weight' => 0.04,
                'dimensions' => '{"length": 4.3, "width": 4.3, "height": 2.7}',
                'stock_quantity' => 800,
                'low_stock_threshold' => 100,
                'manage_stock' => true,
                'in_stock' => true,
                'specifications' => json_encode([
                    'Driver IC' => 'L298N',
                    'Supply Voltage' => '5V-35V',
                    'Logic Voltage' => '5V-7V',
                    'Max Current' => '2A per channel',
                    'Channels' => '2',
                    'Dimensions' => '43x43x27mm',
                ]),
            ],
            [
                'name' => '12V DC Gear Motor High Torque',
                'slug' => '12v-dc-gear-motor-high-torque',
                'sku' => 'MTR-12V-GEAR',
                'description' => 'High torque 12V DC gear motor suitable for robotics applications. RPM: 100-300 depending on gear ratio.',
                'short_description' => '12V DC gear motor high torque',
                'type' => 'variable',
                'category_slug' => 'robot-motors',
                'brand_slug' => null,
                'base_price' => 6.00,
                'currency' => 'USD',
                'weight' => 0.15,
                'dimensions' => '{"length": 7.0, "width": 3.5, "height": 3.5}',
                'stock_quantity' => 400,
                'low_stock_threshold' => 50,
                'manage_stock' => true,
                'in_stock' => true,
                'specifications' => json_encode([
                    'Voltage' => '12V DC',
                    'No-load Speed' => '100-300 RPM',
                    'Torque' => '1-3 kg.cm',
                    'Gear Material' => 'Metal',
                    'Shaft Diameter' => '6mm',
                ]),
            ],
            [
                'name' => '4WD Robot Chassis Kit with Motors',
                'slug' => '4wd-robot-chassis-kit-with-motors',
                'sku' => 'ROBOT-4WD-KIT',
                'description' => 'Complete 4WD robot chassis kit including 4 DC motors, wheels, acrylic chassis, and motor mount. Perfect for building mobile robots.',
                'short_description' => 'Complete 4WD robot platform kit',
                'type' => 'kit',
                'category_slug' => 'robot-kits',
                'brand_slug' => null,
                'base_price' => 35.00,
                'currency' => 'USD',
                'weight' => 0.8,
                'dimensions' => '{"length": 25, "width": 18, "height": 10}',
                'stock_quantity' => 150,
                'low_stock_threshold' => 20,
                'manage_stock' => true,
                'in_stock' => true,
                'specifications' => json_encode([
                    'Material' => 'Acrylic',
                    'Motors' => '4x TT Gear Motors',
                    'Wheels' => '4x 65mm rubber wheels',
                    'Layers' => '2-layer design',
                    'Battery Holder' => 'Included (2x 18650)',
                    'Dimensions' => '180x135x65mm',
                ]),
            ],
            [
                'name' => '18650 Li-ion Battery Cell 3.7V 2600mAh',
                'slug' => '18650-li-ion-battery-cell-3-7v-2600mah',
                'sku' => 'BAT-18650-2600',
                'description' => 'High-quality 18650 Li-ion rechargeable battery cell. 3.7V nominal voltage, 2600mAh capacity. Suitable for power banks, flashlights, and DIY battery packs.',
                'short_description' => '18650 Li-ion 3.7V 2600mAh',
                'type' => 'simple',
                'category_slug' => 'li-ion-cells',
                'brand_slug' => null,
                'base_price' => 3.50,
                'currency' => 'USD',
                'weight' => 0.05,
                'dimensions' => '{"length": 6.5, "width": 1.8, "height": 1.8}',
                'stock_quantity' => 2000,
                'low_stock_threshold' => 200,
                'manage_stock' => true,
                'in_stock' => true,
                'specifications' => json_encode([
                    'Chemistry' => 'Li-ion',
                    'Nominal Voltage' => '3.7V',
                    'Capacity' => '2600mAh',
                    'Max Continuous Discharge' => '5A',
                    'Diameter' => '18mm',
                    'Length' => '65mm',
                    'Weight' => '45g',
                ]),
            ],
            [
                'name' => '2S BMS Board 18650 Lithium Battery Protection',
                'slug' => '2s-bms-board-18650-lithium-battery-protection',
                'sku' => 'BMS-2S-10A',
                'description' => '2S (7.4V) BMS protection board for 18650 Li-ion battery packs. 10A continuous current with overcharge, over-discharge, and short circuit protection.',
                'short_description' => '2S BMS 10A protection board',
                'type' => 'simple',
                'category_slug' => 'bms',
                'brand_slug' => null,
                'base_price' => 2.50,
                'currency' => 'USD',
                'weight' => 0.02,
                'dimensions' => '{"length": 3.5, "width": 1.8, "height": 0.4}',
                'stock_quantity' => 600,
                'low_stock_threshold' => 100,
                'manage_stock' => true,
                'in_stock' => true,
                'specifications' => json_encode([
                    'Configuration' => '2S (7.4V)',
                    'Continuous Current' => '10A',
                    'Overcharge Detection' => '4.25V ±0.05V',
                    'Over-discharge Detection' => '2.5V ±0.1V',
                    'Short Circuit Protection' => 'Yes',
                    'Dimensions' => '35x18x4mm',
                ]),
            ],
            [
                'name' => 'Jumper Wire Set Male-to-Male Female-to-Female Male-to-Female',
                'slug' => 'jumper-wire-set-male-female',
                'sku' => 'CABLE-JUMPER-SET',
                'description' => 'Complete jumper wire set with 40pcs each of Male-to-Male, Female-to-Female, and Male-to-Female wires. Essential for breadboard prototyping.',
                'short_description' => '120pcs jumper wire assortment',
                'type' => 'simple',
                'category_slug' => 'cables',
                'brand_slug' => null,
                'base_price' => 5.00,
                'currency' => 'USD',
                'weight' => 0.1,
                'dimensions' => '{"length": 20, "width": 15, "height": 3}',
                'stock_quantity' => 1000,
                'low_stock_threshold' => 100,
                'manage_stock' => true,
                'in_stock' => true,
                'specifications' => json_encode([
                    'Types Included' => 'M-M, F-F, M-F',
                    'Quantity' => '40pcs each type',
                    'Length' => '20cm',
                    'Color Coded' => 'Yes',
                    'Connector Type' => 'Dupont 2.54mm',
                ]),
            ],
            [
                'name' => 'HC-SR04 Ultrasonic Distance Sensor Module',
                'slug' => 'hc-sr04-ultrasonic-distance-sensor',
                'sku' => 'SENS-HCSR04',
                'description' => 'Popular HC-SR04 ultrasonic distance sensor module. Measures distance from 2cm to 400cm with 3mm accuracy. Easy to interface with Arduino, ESP32, etc.',
                'short_description' => 'Ultrasonic sensor 2cm-400cm range',
                'type' => 'simple',
                'category_slug' => 'sensors',
                'brand_slug' => null,
                'base_price' => 2.00,
                'currency' => 'USD',
                'weight' => 0.02,
                'dimensions' => '{"length": 4.5, "width": 2.0, "height": 1.5}',
                'stock_quantity' => 1500,
                'low_stock_threshold' => 200,
                'manage_stock' => true,
                'in_stock' => true,
                'specifications' => json_encode([
                    'Operating Voltage' => '5V DC',
                    'Range' => '2cm - 400cm',
                    'Accuracy' => '3mm',
                    'Beam Angle' => '<15°',
                    'Trigger Pulse' => '10µs',
                    'Dimensions' => '45x20x15mm',
                ]),
            ],
            [
                'name' => 'Soldering Iron Kit 60W Temperature Adjustable',
                'slug' => 'soldering-iron-kit-60w-adjustable',
                'sku' => 'TOOL-SOLDER-60W',
                'description' => 'Professional 60W soldering iron kit with adjustable temperature (200°C-450°C). Includes stand, sponge, solder wire, desoldering pump, and carrying case.',
                'short_description' => '60W adjustable temp soldering kit',
                'type' => 'simple',
                'category_slug' => 'tools-equipment',
                'brand_slug' => null,
                'base_price' => 18.00,
                'currency' => 'USD',
                'weight' => 0.5,
                'dimensions' => '{"length": 30, "width": 20, "height": 8}',
                'stock_quantity' => 200,
                'low_stock_threshold' => 30,
                'manage_stock' => true,
                'in_stock' => true,
                'specifications' => json_encode([
                    'Power' => '60W',
                    'Temperature Range' => '200°C - 450°C',
                    'Voltage' => '220V / 110V',
                    'Tip Type' => 'Interchangeable',
                    'Heating Time' => '~30 seconds',
                    'Cable Length' => '1.4m',
                ]),
            ],
        ];

        $usd = Currency::where('code', 'USD')->first();
        $npr = Currency::where('code', 'NPR')->first();
        $inr = Currency::where('code', 'INR')->first();

        foreach ($products as $productData) {
            $categorySlug = $productData['category_slug'];
            $brandSlug = $productData['brand_slug'];

            // Find category and brand
            $category = \App\Models\Product\ProductCategory::where('slug', $categorySlug)->first();
            $brand = $brandSlug ? \App\Models\Product\ProductBrand::where('slug', $brandSlug)->first() : null;

            if (!$category) {
                continue;
            }

            // Create Product
            $product = Product::firstOrCreate(
                ['sku' => $productData['sku']],
                [
                    'name' => $productData['name'],
                    'slug' => $productData['slug'],
                    'description' => $productData['description'],
                    'short_description' => $productData['short_description'],
                    'type' => $productData['type'],
                    'product_category_id' => $category->id,
                    'product_brand_id' => $brand?->id,
                    'base_price' => $productData['base_price'],
                    'currency_id' => $usd?->id,
                    'weight' => $productData['weight'],
                    'weight_unit' => 'kg',
                    'dimensions' => $productData['dimensions'],
                    'dimension_unit' => 'cm',
                    'stock_quantity' => $productData['stock_quantity'],
                    'low_stock_threshold' => $productData['low_stock_threshold'],
                    'manage_stock' => $productData['manage_stock'],
                    'in_stock' => $productData['in_stock'],
                    'specifications' => $productData['specifications'],
                    'status' => 'approved',
                    'is_featured' => in_array($productData['slug'], ['esp32-development-board-wifi-bluetooth', 'arduino-uno-r3', '4wd-robot-chassis-kit-with-motors']),
                    'meta_title' => "{$productData['name']} - NeoGiga",
                    'meta_description' => "Buy {$productData['name']} at NeoGiga. {$productData['short_description']}",
                    'meta_keywords' => strtolower(str_replace(',', '', $productData['name'])) . ', electronics, robotics',
                ]
            );

            // Create default variant
            ProductVariant::firstOrCreate(
                ['product_id' => $product->id, 'sku' => $productData['sku']],
                [
                    'name' => 'Default',
                    'price' => $productData['base_price'],
                    'compare_at_price' => null,
                    'cost_price' => $productData['base_price'] * 0.6,
                    'stock_quantity' => $productData['stock_quantity'],
                    'weight' => $productData['weight'],
                    'is_default' => true,
                    'is_active' => true,
                ]
            );

            // Add placeholder image reference
            ProductImage::firstOrCreate(
                ['product_id' => $product->id, 'image_url' => '/images/products/' . $productData['slug'] . '.jpg'],
                [
                    'alt_text' => $productData['name'],
                    'sort_order' => 1,
                    'is_primary' => true,
                ]
            );

            // Create marketplace prices
            if ($globalMp) {
                MarketplaceProductPrice::firstOrCreate(
                    ['marketplace_id' => $globalMp->id, 'product_id' => $product->id],
                    ['price' => $productData['base_price'], 'currency_id' => $usd?->id, 'sale_price' => null, 'active_from' => now(), 'active_to' => null]
                );
            }

            if ($nepalMp) {
                $nprPrice = $productData['base_price'] * 135; // Approx NPR conversion
                MarketplaceProductPrice::firstOrCreate(
                    ['marketplace_id' => $nepalMp->id, 'product_id' => $product->id],
                    ['price' => $nprPrice, 'currency_id' => $npr?->id, 'sale_price' => null, 'active_from' => now(), 'active_to' => null]
                );
            }

            if ($indiaMp) {
                $inrPrice = $productData['base_price'] * 83; // Approx INR conversion
                MarketplaceProductPrice::firstOrCreate(
                    ['marketplace_id' => $indiaMp->id, 'product_id' => $product->id],
                    ['price' => $inrPrice, 'currency_id' => $inr?->id, 'sale_price' => null, 'active_from' => now(), 'active_to' => null]
                );
            }

            // Create inventory stock
            $warehouses = \App\Models\Inventory\Warehouse::limit(3)->get();
            foreach ($warehouses as $warehouse) {
                $stockQty = intval($productData['stock_quantity'] / count($warehouses));
                InventoryStock::firstOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                    [
                        'quantity' => $stockQty,
                        'reserved_quantity' => 0,
                        'damaged_quantity' => 0,
                        'incoming_quantity' => 0,
                        'reorder_level' => $productData['low_stock_threshold'],
                        'last_counted_at' => null,
                    ]
                );
            }
        }
    }
}
