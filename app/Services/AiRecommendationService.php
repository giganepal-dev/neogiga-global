<?php

namespace App\Services;

use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Marketplace\Marketplace;
use Illuminate\Database\Eloquent\Collection;

class AiRecommendationService
{
    /**
     * Generate product recommendations based on user goal.
     * Uses mock logic for now (to be replaced with real AI API).
     *
     * @param string $userGoal
     * @param Marketplace|null $marketplace
     * @return array
     */
    public function recommend(string $userGoal, ?Marketplace $marketplace = null): array
    {
        $goal = strtolower($userGoal);
        
        // Mock rule-based recommendations
        if (str_contains($goal, '4wd') && str_contains($goal, 'robot')) {
            return $this->build4WDRobotCarRecommendations($marketplace);
        }
        
        if (str_contains($goal, 'arduino') && str_contains($goal, 'weather')) {
            return $this->buildWeatherStationRecommendations($marketplace);
        }
        
        if (str_contains($goal, 'solar') && str_contains($goal, 'light')) {
            return $this->buildSolarLightRecommendations($marketplace);
        }
        
        // Default generic recommendation
        return $this->buildGenericRecommendations($userGoal, $marketplace);
    }

    /**
     * Build 4WD Robot Car recommendations.
     */
    protected function build4WDRobotCarRecommendations(?Marketplace $marketplace): array
    {
        $products = [
            [
                'name' => 'ESP32 Development Board',
                'slug' => 'esp32-development-board',
                'quantity' => 1,
                'reason' => 'Main microcontroller with WiFi/Bluetooth for robot control',
                'category' => 'Microcontrollers',
                'estimated_price' => 8.50,
            ],
            [
                'name' => 'L298N Motor Driver',
                'slug' => 'l298n-motor-driver',
                'quantity' => 1,
                'reason' => 'Dual H-bridge driver to control 4 DC motors',
                'category' => 'Motor Drivers',
                'estimated_price' => 3.50,
            ],
            [
                'name' => '12V DC Gear Motor',
                'slug' => '12v-dc-gear-motor',
                'quantity' => 4,
                'reason' => 'High-torque motors for 4-wheel drive',
                'category' => 'Robot Motors',
                'estimated_price' => 5.00,
            ],
            [
                'name' => '4WD Robot Chassis Kit',
                'slug' => '4wd-robot-chassis-kit',
                'quantity' => 1,
                'reason' => 'Acrylic chassis with wheels and motor mounts',
                'category' => 'Robot Chassis',
                'estimated_price' => 15.00,
            ],
            [
                'name' => '18650 Li-ion Battery',
                'slug' => '18650-li-ion-cell',
                'quantity' => 4,
                'reason' => '3.7V 2000mAh cells for 12V battery pack (4S configuration)',
                'category' => 'Li-ion Cells',
                'estimated_price' => 3.00,
            ],
            [
                'name' => '4S BMS Board',
                'slug' => '4s-bms-board',
                'quantity' => 1,
                'reason' => 'Battery management system for 4S Li-ion pack safety',
                'category' => 'BMS',
                'estimated_price' => 2.50,
            ],
            [
                'name' => 'Ultrasonic Sensor HC-SR04',
                'slug' => 'ultrasonic-sensor-hc-sr04',
                'quantity' => 1,
                'reason' => 'Distance measurement for obstacle avoidance',
                'category' => 'Robot Sensors',
                'estimated_price' => 1.50,
            ],
            [
                'name' => 'Jumper Wire Set',
                'slug' => 'jumper-wire-set',
                'quantity' => 1,
                'reason' => 'Male-to-male and male-to-female wires for connections',
                'category' => 'Cables',
                'estimated_price' => 2.00,
            ],
            [
                'name' => 'Toggle Switch',
                'slug' => 'toggle-switch',
                'quantity' => 1,
                'reason' => 'Power on/off switch for robot',
                'category' => 'Switches',
                'estimated_price' => 0.50,
            ],
            [
                'name' => 'Soldering Iron Kit',
                'slug' => 'soldering-iron-kit',
                'quantity' => 1,
                'reason' => 'Required tool for assembling battery pack and connections',
                'category' => 'Soldering Tools',
                'estimated_price' => 12.00,
            ],
        ];

        return [
            'goal' => 'Build a 4WD Robot Car',
            'difficulty' => 'Intermediate',
            'estimated_time' => '4-6 hours',
            'total_estimated_cost' => collect($products)->sum(fn($p) => $p['estimated_price'] * $p['quantity']),
            'products' => $products,
            'required_tools' => [
                'Soldering iron',
                'Wire cutter/stripper',
                'Screwdriver set',
                'Hot glue gun',
                'Multimeter',
            ],
            'safety_notes' => [
                'Handle Li-ion batteries with care - risk of fire if shorted',
                'Use proper BMS to prevent overcharge/overdischarge',
                'Ensure motor driver can handle current draw',
                'Test electronics before connecting battery',
            ],
            'lms_tutorial_slug' => '4wd-robot-car-with-esp32',
            'sample_code_available' => true,
        ];
    }

    /**
     * Build Weather Station recommendations.
     */
    protected function buildWeatherStationRecommendations(?Marketplace $marketplace): array
    {
        $products = [
            [
                'name' => 'Arduino Uno R3',
                'slug' => 'arduino-uno-r3',
                'quantity' => 1,
                'reason' => 'Popular microcontroller board for beginners',
                'category' => 'Arduino',
                'estimated_price' => 12.00,
            ],
            [
                'name' => 'DHT22 Temperature Humidity Sensor',
                'slug' => 'dht22-sensor',
                'quantity' => 1,
                'reason' => 'Accurate temperature and humidity sensing',
                'category' => 'Sensors',
                'estimated_price' => 4.50,
            ],
            [
                'name' => 'BMP280 Barometric Pressure Sensor',
                'slug' => 'bmp280-sensor',
                'quantity' => 1,
                'reason' => 'Measure atmospheric pressure and altitude',
                'category' => 'Sensors',
                'estimated_price' => 3.00,
            ],
            [
                'name' => 'Rain Sensor Module',
                'slug' => 'rain-sensor-module',
                'quantity' => 1,
                'reason' => 'Detect rainfall',
                'category' => 'Sensors',
                'estimated_price' => 2.00,
            ],
            [
                'name' => 'LCD Display 16x2 with I2C',
                'slug' => 'lcd-16x2-i2c',
                'quantity' => 1,
                'reason' => 'Display weather readings locally',
                'category' => 'Modules',
                'estimated_price' => 5.00,
            ],
            [
                'name' => 'Jumper Wire Set',
                'slug' => 'jumper-wire-set',
                'quantity' => 1,
                'reason' => 'Connections between components',
                'category' => 'Cables',
                'estimated_price' => 2.00,
            ],
        ];

        return [
            'goal' => 'Build an Arduino Weather Station',
            'difficulty' => 'Beginner',
            'estimated_time' => '2-3 hours',
            'total_estimated_cost' => collect($products)->sum(fn($p) => $p['estimated_price'] * $p['quantity']),
            'products' => $products,
            'required_tools' => [
                'USB cable for Arduino',
                'Computer with Arduino IDE',
                'Breadboard (optional)',
            ],
            'safety_notes' => [
                'Low voltage project - generally safe',
                'Keep electronics dry when measuring rain',
            ],
            'lms_tutorial_slug' => 'arduino-weather-station',
            'sample_code_available' => true,
        ];
    }

    /**
     * Build Solar Light recommendations.
     */
    protected function buildSolarLightRecommendations(?Marketplace $marketplace): array
    {
        $products = [
            [
                'name' => 'Solar Panel 6V 5W',
                'slug' => 'solar-panel-6v-5w',
                'quantity' => 1,
                'reason' => 'Charge battery during daytime',
                'category' => 'Solar Panels',
                'estimated_price' => 8.00,
            ],
            [
                'name' => '18650 Li-ion Battery',
                'slug' => '18650-li-ion-cell',
                'quantity' => 2,
                'reason' => 'Energy storage for night operation',
                'category' => 'Li-ion Cells',
                'estimated_price' => 3.00,
            ],
            [
                'name' => 'TP4056 Charging Module',
                'slug' => 'tp4056-charging-module',
                'quantity' => 1,
                'reason' => 'Safe Li-ion charging from solar panel',
                'category' => 'Battery Chargers',
                'estimated_price' => 1.50,
            ],
            [
                'name' => 'LED Strip 12V',
                'slug' => 'led-strip-12v',
                'quantity' => 1,
                'reason' => 'Light source',
                'category' => 'Modules',
                'estimated_price' => 4.00,
            ],
            [
                'name' => 'LDR Photoresistor',
                'slug' => 'ldr-photoresistor',
                'quantity' => 1,
                'reason' => 'Auto on/off based on light level',
                'category' => 'Sensors',
                'estimated_price' => 0.50,
            ],
        ];

        return [
            'goal' => 'Build a Solar Powered Light',
            'difficulty' => 'Beginner',
            'estimated_time' => '1-2 hours',
            'total_estimated_cost' => collect($products)->sum(fn($p) => $p['estimated_price'] * $p['quantity']),
            'products' => $products,
            'required_tools' => [
                'Soldering iron',
                'Wire cutter',
                'Electrical tape',
            ],
            'safety_notes' => [
                'Handle Li-ion batteries carefully',
                'Ensure waterproof enclosure for outdoor use',
            ],
            'lms_tutorial_slug' => 'solar-powered-light',
            'sample_code_available' => false,
        ];
    }

    /**
     * Build generic recommendations for unrecognized goals.
     */
    protected function buildGenericRecommendations(string $userGoal, ?Marketplace $marketplace): array
    {
        return [
            'goal' => $userGoal,
            'difficulty' => 'Unknown',
            'estimated_time' => 'Varies',
            'total_estimated_cost' => 0,
            'products' => [],
            'message' => 'We could not match your goal to a predefined project. Please browse our categories or contact support for assistance.',
            'required_tools' => [],
            'safety_notes' => ['Always follow safety guidelines when working with electronics'],
            'lms_tutorial_slug' => null,
            'sample_code_available' => false,
        ];
    }

    /**
     * Find products by slug from database.
     *
     * @param array $recommendations
     * @return array
     */
    public function enrichWithProducts(array $recommendations): array
    {
        if (!isset($recommendations['products']) || empty($recommendations['products'])) {
            return $recommendations;
        }

        $slugs = collect($recommendations['products'])->pluck('slug')->toArray();
        $products = Product::whereIn('slug', $slugs)
            ->with(['defaultImage', 'category'])
            ->get()
            ->keyBy('slug');

        foreach ($recommendations['products'] as &$product) {
            $slug = $product['slug'];
            if (isset($products[$slug])) {
                $dbProduct = $products[$slug];
                $product['product_id'] = $dbProduct->id;
                $product['in_stock'] = $dbProduct->stockAvailable();
                $product['image_url'] = $dbProduct->defaultImage?->url ?? null;
            } else {
                $product['product_id'] = null;
                $product['in_stock'] = false;
                $product['image_url'] = null;
            }
        }

        return $recommendations;
    }
}
