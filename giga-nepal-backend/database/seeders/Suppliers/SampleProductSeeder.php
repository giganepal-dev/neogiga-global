<?php

namespace Database\Seeders\Suppliers;

use App\Models\Marketplace\Product;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\ProductSupplier;
use App\Models\Marketplace\ProductCountryPrice;
use App\Models\Marketplace\ProductWarehouse;
use App\Models\Marketplace\ProductAiFeature;
use App\Models\Marketplace\ProductResource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adafruit = Supplier::where('slug', 'adafruit')->first();
        $waveshare = Supplier::where('slug', 'waveshare')->first();
        $sparkfun = Supplier::where('slug', 'sparkfun')->first();
        $seeed = Supplier::where('slug', 'seeed-studio')->first();
        $dfrobot = Supplier::where('slug', 'dfrobot')->first();
        $okystar = Supplier::where('slug', 'okystar')->first();

        $sampleProducts = [
            // Adafruit Products
            [
                'supplier' => $adafruit,
                'name' => 'Adafruit Feather M4 Express - Featuring ATSAMD51',
                'manufacturer' => 'Adafruit',
                'brand' => 'Adafruit',
                'mpn' => '3857',
                'sku' => 'AFR-3857',
                'upc_ean' => null,
                'category' => 'Development Boards',
                'subcategory' => 'Arduino Compatible',
                'description' => 'The Adafruit Feather M4 Express is the latest Feather board with awesome performance and no WiFi. It comes with an ATSAMD51 Cortex M4 core running at 120 MHz, over 2x faster than the M0.',
                'specifications' => json_encode([
                    'microcontroller' => 'ATSAMD51J19',
                    'clock_speed' => '120 MHz',
                    'flash_memory' => '512 KB',
                    'sram' => '192 KB',
                    'digital_io_pins' => 22,
                    'analog_inputs' => 6,
                    'dac_outputs' => 1,
                    'uart' => 2,
                    'spi' => 1,
                    'i2c' => 1,
                    'usb' => 'Native USB support',
                    'battery_charger' => 'LiPoly charger built-in',
                    'dimensions' => '51mm x 23mm x 7mm',
                    'weight' => '5g'
                ]),
                'features' => json_encode([
                    'Cortex M4 @ 120MHz',
                    '512KB Flash, 192KB RAM',
                    'Native USB support',
                    'Built-in LiPoly battery charger',
                    'STEMMA QT/Qwiic connector',
                    'Arduino & CircuitPython compatible',
                    'Auto-reset for bootloader entry'
                ]),
                'applications' => json_encode([
                    'IoT projects',
                    'Wearables',
                    'Data logging',
                    'Robotics control',
                    'Sensor interfacing'
                ]),
                'compatible_boards' => json_encode(['Feather Wing ecosystem']),
                'price_usd' => 19.95,
                'stock_quantity' => 150,
                'images' => json_encode([
                    'https://cdn-shop.adafruit.com/1200x900/3857-01.jpg',
                    'https://cdn-shop.adafruit.com/1200x900/3857-02.jpg',
                    'https://cdn-shop.adafruit.com/1200x900/3857-03.jpg'
                ]),
                'datasheet_url' => 'https://learn.adafruit.com/assets/70839',
                'github_repo' => 'https://github.com/adafruit/Adafruit_SAMD_Core',
                'arduino_library' => 'Adafruit SAMD Boards',
                'platformio_id' => 'board_feather_m4',
                'circuitpython_compatible' => true,
            ],
            [
                'supplier' => $adafruit,
                'name' => 'Adafruit HUZZAH32 – ESP32 Feather Board',
                'manufacturer' => 'Adafruit',
                'brand' => 'Adafruit',
                'mpn' => '3405',
                'sku' => 'AFR-3405',
                'upc_ean' => null,
                'category' => 'Development Boards',
                'subcategory' => 'ESP32',
                'description' => 'Adafruit HUZZAH32 is the ultimate IoT development board featuring the ESP32 chip with WiFi and Bluetooth capabilities.',
                'specifications' => json_encode([
                    'microcontroller' => 'ESP32-WROOM-32',
                    'cpu_cores' => 2,
                    'clock_speed' => '240 MHz',
                    'flash_memory' => '4 MB',
                    'sram' => '520 KB',
                    'wifi' => '802.11 b/g/n',
                    'bluetooth' => 'v4.2 BR/EDR and BLE',
                    'digital_io_pins' => 20,
                    'analog_inputs' => 6,
                    'adc_bits' => 12,
                    'dac_outputs' => 2,
                    'dimensions' => '51mm x 23mm x 7mm',
                    'weight' => '5g'
                ]),
                'features' => json_encode([
                    'Dual-core 240MHz ESP32',
                    'WiFi + Bluetooth',
                    'Built-in LiPoly battery charger',
                    'USB-to-Serial converter',
                    'Deep sleep modes',
                    'Arduino & MicroPython compatible'
                ]),
                'applications' => json_encode([
                    'IoT sensors',
                    'Smart home devices',
                    'Wireless controllers',
                    'Bluetooth beacons',
                    'Remote monitoring'
                ]),
                'compatible_boards' => json_encode(['Feather Wing ecosystem']),
                'price_usd' => 16.95,
                'stock_quantity' => 200,
                'images' => json_encode([
                    'https://cdn-shop.adafruit.com/1200x900/3405-01.jpg',
                    'https://cdn-shop.adafruit.com/1200x900/3405-02.jpg'
                ]),
                'datasheet_url' => 'https://www.espressif.com/sites/default/files/documentation/esp32_datasheet_en.pdf',
                'github_repo' => 'https://github.com/espressif/arduino-esp32',
                'arduino_library' => 'ESP32 by Espressif',
                'platformio_id' => 'board_feather_esp32',
                'circuitpython_compatible' => true,
            ],
            
            // Waveshare Products
            [
                'supplier' => $waveshare,
                'name' => 'Waveshare 7inch HDMI LCD (C) Capacitive Touch Screen',
                'manufacturer' => 'Waveshare',
                'brand' => 'Waveshare',
                'mpn' => '15005',
                'sku' => 'WS-15005',
                'upc_ean' => '6939318215005',
                'category' => 'Displays',
                'subcategory' => 'Touch Screens',
                'description' => '7-inch IPS capacitive touch screen with 1024×600 resolution, HDMI interface, supports Raspberry Pi and other single-board computers.',
                'specifications' => json_encode([
                    'screen_size' => '7 inches',
                    'resolution' => '1024x600',
                    'display_type' => 'IPS LCD',
                    'touch_type' => 'Capacitive (5-point)',
                    'interface' => 'HDMI + USB',
                    'brightness' => '450 cd/m²',
                    'contrast_ratio' => '800:1',
                    'viewing_angle' => '170°',
                    'power_supply' => '5V DC',
                    'dimensions' => '165mm x 100mm x 6mm',
                    'weight' => '220g'
                ]),
                'features' => json_encode([
                    '1024×600 hardware resolution',
                    '5-point capacitive touch',
                    'IPS display technology',
                    'Plug and play for Raspberry Pi',
                    'Supports Windows/Linux/Android',
                    'Backlight controllable via PWM'
                ]),
                'applications' => json_encode([
                    'Raspberry Pi projects',
                    'Industrial HMI',
                    'Kiosk displays',
                    'Embedded systems',
                    'DIY gaming consoles'
                ]),
                'compatible_boards' => json_encode([
                    'Raspberry Pi 4/3/2/Zero',
                    'BeagleBone Black',
                    'Banana Pi',
                    'Any HDMI device'
                ]),
                'price_usd' => 69.99,
                'stock_quantity' => 85,
                'images' => json_encode([
                    'https://www.waveshare.com/media/catalog/product/1/5/15005_1.jpg',
                    'https://www.waveshare.com/media/catalog/product/1/5/15005_2.jpg'
                ]),
                'datasheet_url' => 'https://www.waveshare.com/w/upload/d/d5/7inch_HDMI_LCD_C.pdf',
                'github_repo' => 'https://github.com/waveshare/LCD-show',
                'arduino_library' => null,
                'platformio_id' => null,
                'circuitpython_compatible' => false,
            ],
            [
                'supplier' => $waveshare,
                'name' => 'Waveshare 2.13inch e-Paper Display Module (B)',
                'manufacturer' => 'Waveshare',
                'brand' => 'Waveshare',
                'mpn' => '11005',
                'sku' => 'WS-11005',
                'upc_ean' => '6939318211005',
                'category' => 'Displays',
                'subcategory' => 'E-Paper',
                'description' => '2.13-inch tri-color e-paper display module with SPI interface, ultra-low power consumption, perfect for battery-powered applications.',
                'specifications' => json_encode([
                    'screen_size' => '2.13 inches',
                    'resolution' => '122x250 pixels',
                    'display_colors' => 'Black, White, Red',
                    'interface' => 'SPI',
                    'operating_voltage' => '3.3V / 5V',
                    'refresh_time' => '~15 seconds',
                    'viewing_angle' => '>170°',
                    'ultra_low_power' => 'Power only during refresh',
                    'dimensions' => '42mm x 12mm x 1mm',
                    'weight' => '3g'
                ]),
                'features' => json_encode([
                    'Tri-color display (Black/White/Red)',
                    'Ultra-low power consumption',
                    'Wide viewing angle',
                    'Readable in sunlight',
                    'Memory effect (image holds without power)',
                    'SPI interface'
                ]),
                'applications' => json_encode([
                    'E-labels',
                    'Weather stations',
                    'Smart badges',
                    'Low-power sensors',
                    'Information displays'
                ]),
                'compatible_boards' => json_encode([
                    'Raspberry Pi',
                    'Arduino',
                    'ESP32',
                    'STM32',
                    'Jetson Nano'
                ]),
                'price_usd' => 24.99,
                'stock_quantity' => 120,
                'images' => json_encode([
                    'https://www.waveshare.com/media/catalog/product/1/1/11005_1.jpg',
                    'https://www.waveshare.com/media/catalog/product/1/1/11005_2.jpg'
                ]),
                'datasheet_url' => 'https://www.waveshare.com/w/upload/a/a2/2.13inch_e-Paper_Module_B.pdf',
                'github_repo' => 'https://github.com/waveshare/e-Paper',
                'arduino_library' => 'Waveshare e-Paper Library',
                'platformio_id' => null,
                'circuitpython_compatible' => true,
            ],
            
            // SparkFun Products
            [
                'supplier' => $sparkfun,
                'name' => 'SparkFun Pro Micro - ATmega32U4',
                'manufacturer' => 'SparkFun',
                'brand' => 'SparkFun',
                'mpn' => 'DEV-12640',
                'sku' => 'SF-DEV-12640',
                'upc_ean' => null,
                'category' => 'Development Boards',
                'subcategory' => 'Arduino Compatible',
                'description' => 'The SparkFun Pro Micro is a microcontroller board based on the ATmega32U4, featuring native USB support and Arduino Leonardo compatibility.',
                'specifications' => json_encode([
                    'microcontroller' => 'ATmega32U4',
                    'clock_speed' => '16 MHz',
                    'flash_memory' => '32 KB',
                    'sram' => '2.5 KB',
                    'eeprom' => '1 KB',
                    'digital_io_pins' => 18,
                    'analog_inputs' => 12,
                    'pwm_pins' => 7,
                    'uart' => 1,
                    'spi' => 1,
                    'i2c' => 1,
                    'usb' => 'Native USB',
                    'dimensions' => '33mm x 18mm x 7mm',
                    'weight' => '2g'
                ]),
                'features' => json_encode([
                    'ATmega32U4 with native USB',
                    'Arduino Leonardo compatible',
                    'Compact form factor',
                    '3.3V or 5V operation selectable',
                    'Two reset buttons (one for bootloader)',
                    '12-bit ADC support'
                ]),
                'applications' => json_encode([
                    'Wearables',
                    'Keyboards and HID devices',
                    'Small robotics',
                    'Sensor nodes',
                    'Custom USB devices'
                ]),
                'compatible_boards' => json_encode(['Arduino Leonardo ecosystem']),
                'price_usd' => 19.95,
                'stock_quantity' => 175,
                'images' => json_encode([
                    'https://cdn.sparkfun.com/assets/1/3/2/2/5/5302f77ace395fd86b0b0000.jpg',
                    'https://cdn.sparkfun.com/assets/1/3/2/2/5/5302f77ace395fd86b0b0001.jpg'
                ]),
                'datasheet_url' => 'https://cdn.sparkfun.com/datasheets/BreakoutBoards/atmel_ATmega32U4.pdf',
                'github_repo' => 'https://github.com/sparkfun/Arduino_Boards',
                'arduino_library' => 'Arduino AVR Boards',
                'platformio_id' => 'board_pro_micro',
                'circuitpython_compatible' => true,
            ],
            [
                'supplier' => $sparkfun,
                'name' => 'SparkFun Qwiic Shield for Arduino Uno',
                'manufacturer' => 'SparkFun',
                'brand' => 'SparkFun',
                'mpn' => 'DEV-14459',
                'sku' => 'SF-DEV-14459',
                'upc_ean' => null,
                'category' => 'Shields & Add-ons',
                'subcategory' => 'Arduino Shields',
                'description' => 'Add Qwiic connectivity to your Arduino Uno with this shield featuring four Qwiic connectors for easy I2C sensor integration.',
                'specifications' => json_encode([
                    'interface' => 'I2C (Qwiic)',
                    'connectors' => 4,
                    'input_voltage' => '5V from Arduino',
                    'logic_level' => '5V',
                    'dimensions' => '53mm x 68mm',
                    'weight' => '15g'
                ]),
                'features' => json_encode([
                    'Four Qwiic connectors',
                    'Plug-and-play I2C',
                    'No soldering required',
                    'Compatible with Arduino Uno',
                    'LED power indicator',
                    'Pull-up resistors included'
                ]),
                'applications' => json_encode([
                    'Multi-sensor projects',
                    'IoT prototyping',
                    'Data acquisition',
                    'Environmental monitoring',
                    'Robotics'
                ]),
                'compatible_boards' => json_encode(['Arduino Uno', 'Arduino Mega']),
                'price_usd' => 9.95,
                'stock_quantity' => 250,
                'images' => json_encode([
                    'https://cdn.sparkfun.com/assets/parts/1/2/8/5/7/14459-01.jpg'
                ]),
                'datasheet_url' => 'https://cdn.sparkfun.com/assets/learn_tutorials/8/2/3/Qwiic_Shield_Uno_Documentation.pdf',
                'github_repo' => 'https://github.com/sparkfun/Qwiic_Shield_Arduino_Uno',
                'arduino_library' => null,
                'platformio_id' => null,
                'circuitpython_compatible' => false,
            ],
            
            // Seeed Studio Products
            [
                'supplier' => $seeed,
                'name' => 'Seeed Studio XIAO ESP32C3',
                'manufacturer' => 'Seeed Studio',
                'brand' => 'Seeed Studio',
                'mpn' => '102110620',
                'sku' => 'SEE-102110620',
                'upc_ean' => null,
                'category' => 'Development Boards',
                'subcategory' => 'ESP32',
                'description' => 'XIAO ESP32C3 is a tiny but powerful development board featuring RISC-V architecture, WiFi, and Bluetooth 5 LE.',
                'specifications' => json_encode([
                    'microcontroller' => 'ESP32-C3',
                    'architecture' => 'RISC-V 32-bit',
                    'clock_speed' => '160 MHz',
                    'flash_memory' => '4 MB',
                    'sram' => '400 KB',
                    'wifi' => '802.11 b/g/n',
                    'bluetooth' => 'Bluetooth 5 LE',
                    'digital_io_pins' => 14,
                    'analog_inputs' => 4,
                    'adc_bits' => 12,
                    'uart' => 2,
                    'spi' => 2,
                    'i2c' => 1,
                    'dimensions' => '21mm x 17.5mm x 8mm',
                    'weight' => '3g'
                ]),
                'features' => json_encode([
                    'RISC-V 32-bit @ 160MHz',
                    'WiFi + Bluetooth 5 LE',
                    'Tiny form factor (thumb-sized)',
                    'Battery charging circuit',
                    'Deep sleep mode',
                    'Arduino, CircuitPython, MicroPython compatible'
                ]),
                'applications' => json_encode([
                    'Wearable devices',
                    'IoT sensors',
                    'Smart home',
                    'Mini robots',
                    'Wireless controllers'
                ]),
                'compatible_boards' => json_encode(['XIAO expansion boards']),
                'price_usd' => 9.90,
                'stock_quantity' => 300,
                'images' => json_encode([
                    'https://files.seeedstudio.com/wiki/SeeedStudio-XIAO-ESP32C3/img/xiao-esp32c3.png'
                ]),
                'datasheet_url' => 'https://www.espressif.com/sites/default/files/documentation/esp32-c3_datasheet_en.pdf',
                'github_repo' => 'https://github.com/Seeed-Studio/ArduinoCore-seeed',
                'arduino_library' => 'Seeed Studio XIAO',
                'platformio_id' => 'board_seeed_xiao_esp32c3',
                'circuitpython_compatible' => true,
            ],
            [
                'supplier' => $seeed,
                'name' => 'Grove - Temperature Sensor (DS18B20)',
                'manufacturer' => 'Seeed Studio',
                'brand' => 'Seeed Studio',
                'mpn' => '101020019',
                'sku' => 'SEE-101020019',
                'upc_ean' => null,
                'category' => 'Sensors',
                'subcategory' => 'Temperature',
                'description' => 'Grove-compatible waterproof temperature sensor using DS18B20 chip, perfect for outdoor and liquid temperature measurements.',
                'specifications' => json_encode([
                    'sensor_chip' => 'DS18B20',
                    'temperature_range' => '-55°C to +125°C',
                    'accuracy' => '±0.5°C',
                    'resolution' => '9-12 bit configurable',
                    'interface' => '1-Wire',
                    'cable_length' => '1 meter',
                    'waterproof_rating' => 'IP67',
                    'supply_voltage' => '3.3V - 5V',
                    'dimensions' => 'Probe: 6mm diameter x 50mm length'
                ]),
                'features' => json_encode([
                    'Waterproof stainless steel probe',
                    'Grove connector for easy connection',
                    'Unique 64-bit serial code',
                    'Multiple sensors on one bus',
                    'High accuracy',
                    'Wide temperature range'
                ]),
                'applications' => json_encode([
                    'Weather stations',
                    'Aquarium monitoring',
                    'Soil temperature sensing',
                    'HVAC systems',
                    'Food storage monitoring'
                ]),
                'compatible_boards' => json_encode([
                    'Arduino',
                    'Raspberry Pi',
                    'ESP32',
                    'Any Grove system'
                ]),
                'price_usd' => 8.90,
                'stock_quantity' => 400,
                'images' => json_encode([
                    'https://files.seeedstudio.com/wiki/Grove_Temperature_Sensor/img/main.jpg'
                ]),
                'datasheet_url' => 'https://files.seeedstudio.com/wiki/Grove_Temperature_Sensor/res/DS18B20.pdf',
                'github_repo' => 'https://github.com/Seeed-Studio/Grove_Temperature_And_Humidity_Sensor',
                'arduino_library' => 'OneWire + DallasTemperature',
                'platformio_id' => null,
                'circuitpython_compatible' => true,
            ],
            
            // DFRobot Products
            [
                'supplier' => $dfrobot,
                'name' => 'DFRobot FireBeetle 2 ESP32-E IoT Microcontroller',
                'manufacturer' => 'DFRobot',
                'brand' => 'DFRobot',
                'mpn' => 'DFR0654',
                'sku' => 'DFR-DFR0654',
                'upc_ean' => null,
                'category' => 'Development Boards',
                'subcategory' => 'ESP32',
                'description' => 'FireBeetle 2 ESP32-E is designed for low-power IoT applications with advanced power management and compact design.',
                'specifications' => json_encode([
                    'microcontroller' => 'ESP32-D0WDQ6',
                    'clock_speed' => '240 MHz',
                    'flash_memory' => '4 MB',
                    'sram' => '520 KB',
                    'wifi' => '802.11 b/g/n',
                    'bluetooth' => 'v4.2 BR/EDR and BLE',
                    'digital_io_pins' => 16,
                    'analog_inputs' => 8,
                    'adc_bits' => 12,
                    'low_power_modes' => 'Deep sleep, Light sleep',
                    'battery_connector' => 'PH2.0 2-pin',
                    'dimensions' => '40mm x 25mm x 6mm',
                    'weight' => '4g'
                ]),
                'features' => json_encode([
                    'Ultra-low power consumption',
                    'Onboard battery charging',
                    'Compact size',
                    'Castellated holes for SMD mounting',
                    'Arduino & MicroPython compatible',
                    'Built-in antenna'
                ]),
                'applications' => json_encode([
                    'Battery-powered IoT',
                    'Wearable electronics',
                    'Environmental sensors',
                    'Asset tracking',
                    'Smart agriculture'
                ]),
                'compatible_boards' => json_encode(['FireBeetle ecosystem']),
                'price_usd' => 14.90,
                'stock_quantity' => 180,
                'images' => json_encode([
                    'https://global.dfrobot.com/image/cache/data/DFR0654/DFR0654-1-800x800.jpg'
                ]),
                'datasheet_url' => 'https://www.dfrobot.com/docs/en/DFR0654.html',
                'github_repo' => 'https://github.com/DFRobot/DFRobot_FireBeetle2',
                'arduino_library' => 'ESP32 by Espressif',
                'platformio_id' => 'board_firebeetle2_esp32',
                'circuitpython_compatible' => true,
            ],
            [
                'supplier' => $dfrobot,
                'name' => 'Gravity: Analog Ultrasonic Sensor V2',
                'manufacturer' => 'DFRobot',
                'brand' => 'DFRobot',
                'mpn' => 'SEN0311',
                'sku' => 'DFR-SEN0311',
                'upc_ean' => null,
                'category' => 'Sensors',
                'subcategory' => 'Distance',
                'description' => 'Analog ultrasonic distance sensor with improved stability and anti-interference capability, featuring Gravity interface.',
                'specifications' => json_encode([
                    'detection_range' => '2cm - 400cm',
                    'output_signal' => 'Analog voltage',
                    'operating_voltage' => '3.3V - 5V',
                    'operating_current' => '<20mA',
                    'beam_angle' => '15 degrees',
                    'frequency' => '40kHz',
                    'response_time' => '<50ms',
                    'dimensions' => '45mm x 25mm x 15mm',
                    'weight' => '10g'
                ]),
                'features' => json_encode([
                    'Gravity interface (plug-and-play)',
                    'Improved anti-interference',
                    'Wide detection range',
                    'Analog output',
                    'Stable performance',
                    'Easy integration'
                ]),
                'applications' => json_encode([
                    'Obstacle avoidance robots',
                    'Level measurement',
                    'Proximity detection',
                    'Parking sensors',
                    'Security systems'
                ]),
                'compatible_boards' => json_encode([
                    'Arduino',
                    'Raspberry Pi',
                    'ESP32',
                    'Any analog input board'
                ]),
                'price_usd' => 12.90,
                'stock_quantity' => 220,
                'images' => json_encode([
                    'https://global.dfrobot.com/image/cache/data/SEN0311/SEN0311-1-800x800.jpg'
                ]),
                'datasheet_url' => 'https://wiki.dfrobot.com/Gravity__Analog_Ultrasonic_Sensor_V2_SKU_SEN0311',
                'github_repo' => 'https://github.com/DFRobot/DFRobot_Ultrasonic',
                'arduino_library' => 'DFRobot_Ultrasonic',
                'platformio_id' => null,
                'circuitpython_compatible' => true,
            ],
        ];

        DB::transaction(function () use ($sampleProducts) {
            foreach ($sampleProducts as $productData) {
                $supplier = $productData['supplier'];
                if (!$supplier) continue;

                unset($productData['supplier']);
                
                // Create or update product
                $product = Product::updateOrCreate(
                    ['sku' => $productData['sku']],
                    array_merge($productData, [
                        'status' => 'draft',
                        'is_visible' => false,
                    ])
                );

                // Create product-supplier relationship
                ProductSupplier::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'supplier_id' => $supplier->id,
                    ],
                    [
                        'manufacturer_part_number' => $productData['mpn'],
                        'supplier_sku' => $productData['sku'],
                        'cost_price' => $productData['price_usd'] * 0.7, // 30% margin assumption
                        'lead_time_days' => rand(7, 21),
                        'minimum_order_quantity' => 1,
                        'is_preferred' => true,
                    ]
                );

                // Create country pricing (USD, EUR, GBP, INR)
                $countryPrices = [
                    ['country_code' => 'US', 'currency' => 'USD', 'price' => $productData['price_usd']],
                    ['country_code' => 'EU', 'currency' => 'EUR', 'price' => $productData['price_usd'] * 0.92],
                    ['country_code' => 'GB', 'currency' => 'GBP', 'price' => $productData['price_usd'] * 0.79],
                    ['country_code' => 'IN', 'currency' => 'INR', 'price' => $productData['price_usd'] * 83],
                ];

                foreach ($countryPrices as $cp) {
                    ProductCountryPrice::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'country_code' => $cp['country_code'],
                        ],
                        [
                            'currency' => $cp['currency'],
                            'price' => round($cp['price'], 2),
                            'bulk_pricing' => json_encode([
                                ['min_qty' => 1, 'max_qty' => 9, 'discount_percent' => 0],
                                ['min_qty' => 10, 'max_qty' => 49, 'discount_percent' => 5],
                                ['min_qty' => 50, 'max_qty' => 99, 'discount_percent' => 10],
                                ['min_qty' => 100, 'max_qty' => null, 'discount_percent' => 15],
                            ]),
                        ]
                    );
                }

                // Create warehouse inventory
                ProductWarehouse::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'warehouse_id' => 1, // Default warehouse
                    ],
                    [
                        'quantity_on_hand' => $productData['stock_quantity'],
                        'quantity_reserved' => 0,
                        'quantity_available' => $productData['stock_quantity'],
                        'reorder_point' => 20,
                        'reorder_quantity' => 50,
                    ]
                );

                // Create AI features placeholder
                ProductAiFeature::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'ai_summary' => null, // To be generated by AI service
                        'ai_bom_suggestions' => null,
                        'ai_alternatives' => null,
                        'ai_cross_sell' => null,
                        'ai_project_ideas' => null,
                        'has_pinout_diagram' => false,
                        'pinout_diagram_path' => null,
                        'wiring_examples' => null,
                        'datasheet_qa_pairs' => null,
                        'last_ai_update' => null,
                    ]
                );

                // Create resources
                $resources = [];
                
                if (!empty($productData['datasheet_url'])) {
                    $resources[] = [
                        'product_id' => $product->id,
                        'type' => 'datasheet',
                        'title' => 'Product Datasheet',
                        'url' => $productData['datasheet_url'],
                        'file_path' => null,
                        'file_size' => null,
                        'is_downloadable' => true,
                    ];
                }

                if (!empty($productData['github_repo'])) {
                    $resources[] = [
                        'product_id' => $product->id,
                        'type' => 'github_example',
                        'title' => 'GitHub Repository',
                        'url' => $productData['github_repo'],
                        'file_path' => null,
                        'file_size' => null,
                        'is_downloadable' => false,
                    ];
                }

                if (!empty($productData['arduino_library'])) {
                    $resources[] = [
                        'product_id' => $product->id,
                        'type' => 'arduino_library',
                        'title' => $productData['arduino_library'],
                        'url' => null,
                        'file_path' => null,
                        'file_size' => null,
                        'is_downloadable' => false,
                    ];
                }

                if (!empty($productData['platformio_id'])) {
                    $resources[] = [
                        'product_id' => $product->id,
                        'type' => 'platformio_library',
                        'title' => 'PlatformIO Library',
                        'url' => "https://registry.platformio.org/libraries/search?query={$productData['platformio_id']}",
                        'file_path' => null,
                        'file_size' => null,
                        'is_downloadable' => false,
                    ];
                }

                if ($productData['circuitpython_compatible']) {
                    $resources[] = [
                        'product_id' => $product->id,
                        'type' => 'circuitpython_library',
                        'title' => 'CircuitPython Compatible',
                        'url' => 'https://circuitpython.org/',
                        'file_path' => null,
                        'file_size' => null,
                        'is_downloadable' => false,
                    ];
                }

                foreach ($resources as $resource) {
                    ProductResource::updateOrCreate(
                        [
                            'product_id' => $resource['product_id'],
                            'type' => $resource['type'],
                        ],
                        $resource
                    );
                }
            }
        });

        $this->command->info('✓ Seeded ' . count($sampleProducts) . ' sample products with complete data');
        $this->command->info('  - Product-supplier relationships created');
        $this->command->info('  - Country-wise pricing (USD, EUR, GBP, INR)');
        $this->command->info('  - Warehouse inventory records');
        $this->command->info('  - AI feature placeholders');
        $this->command->info('  - Resource links (datasheets, GitHub, libraries)');
    }
}
