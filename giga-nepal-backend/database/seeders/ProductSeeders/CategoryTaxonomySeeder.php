<?php

namespace Database\Seeders\ProductSeeders;

use App\Models\Marketplace\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * NeoGiga engineering taxonomy — 27 root domains (Blueprint category seed).
 *
 * Idempotent: matched by slug via firstOrCreate; safe to re-run.
 * Each category carries SEO meta and marketplace visibility flags
 * (global + in + np) plus related LMS topic hints in seo_meta.
 */
class CategoryTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $sort = 0;

        foreach (self::taxonomy() as $root) {
            $parent = $this->upsert($root, null, $sort += 10);

            foreach ($root['children'] ?? [] as $i => $child) {
                $this->upsert(['name' => $child], $parent->id, ($i + 1) * 10);
            }
        }
    }

    protected function upsert(array $data, ?int $parentId, int $sort): ProductCategory
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);

        $category = ProductCategory::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $data['name'],
                'parent_id' => $parentId,
                'description' => $data['description'] ?? null,
                'icon_path' => $data['icon'] ?? null,
                'sort_order' => $sort,
                'is_active' => true,
                'is_featured' => $data['featured'] ?? false,
                'marketplace_visibility' => ['global' => true, 'in' => true, 'np' => true],
                'seo_meta' => [
                    'title' => ($data['seo_title'] ?? $data['name']).' | NeoGiga',
                    'description' => $data['seo_description']
                        ?? ($data['description'] ?? "Shop {$data['name']} on NeoGiga — genuine parts, regional stock, engineering support."),
                    'lms_topics' => $data['lms_topics'] ?? [],
                    'homepage_visible' => $data['featured'] ?? false,
                ],
            ],
        );

        // Converge only the governed structural fields. Existing copy, images,
        // translations and SEO edits are intentionally preserved.
        $structural = [
            'parent_id' => $parentId,
            'sort_order' => $sort,
            'is_active' => true,
            'seo_meta' => array_merge($category->seo_meta ?? [], [
                'neogiga_taxonomy_level' => $parentId === null ? 'root' : 'subcategory',
            ]),
        ];

        if ($category->only(array_keys($structural)) !== $structural) {
            $category->update($structural);
        }

        return $category;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function taxonomy(): array
    {
        return [
            ['name' => 'Semiconductors', 'icon' => 'semiconductors', 'featured' => true,
                'description' => 'Integrated circuits, microcontrollers, discretes, memory, logic and analog devices.',
                'seo_title' => 'Semiconductors — ICs, MCUs, Discretes',
                'lms_topics' => ['embedded-c', 'digital-electronics'],
                'children' => ['Microcontrollers', 'Microprocessors & SoCs', 'Memory', 'Analog ICs', 'Logic ICs', 'Discrete Semiconductors', 'Optoelectronics', 'RF Semiconductors']],
            ['name' => 'Electronic Components', 'icon' => 'components', 'featured' => true,
                'description' => 'Passive components, connectors, electromechanical parts and displays.',
                'lms_topics' => ['circuit-basics'],
                'children' => ['Resistors', 'Capacitors', 'Inductors', 'Connectors', 'Switches & Relays', 'Displays', 'Crystals & Oscillators', 'Fuses & Protection']],
            ['name' => 'Embedded Systems', 'icon' => 'embedded', 'featured' => true,
                'description' => 'Development boards, SBCs, modules and programmers/debuggers.',
                'lms_topics' => ['embedded-c', 'rtos'],
                'children' => ['Development Boards', 'Single-Board Computers', 'FPGA & CPLD', 'Programmers & Debuggers', 'Embedded Modules']],
            ['name' => 'Sensors', 'icon' => 'sensors',
                'description' => 'Sensing for temperature, motion, pressure, gas, vision and more.',
                'lms_topics' => ['sensor-interfacing'],
                'children' => ['Temperature & Humidity', 'Motion & IMU', 'Pressure & Force', 'Gas & Environmental', 'Proximity & Distance', 'Image & Vision', 'Current & Voltage']],
            ['name' => 'IoT & Wireless', 'icon' => 'iot', 'featured' => true,
                'description' => 'Connectivity modules and gateways: WiFi, BLE, LoRa, cellular, GNSS.',
                'lms_topics' => ['iot-protocols', 'mqtt'],
                'children' => ['WiFi Modules', 'Bluetooth & BLE', 'LoRa & LPWAN', 'Cellular (4G/5G/NB-IoT)', 'GNSS/GPS', 'Zigbee & Thread', 'IoT Gateways', 'Antennas']],
            ['name' => 'Robotics', 'icon' => 'robotics', 'featured' => true,
                'description' => 'Motors, drivers, actuators, chassis, controllers and robot kits.',
                'lms_topics' => ['robotics-101', 'ros'],
                'children' => ['DC & Gear Motors', 'Stepper Motors', 'Servo Motors', 'Motor Drivers', 'Actuators', 'Robot Chassis & Frames', 'Robot Kits', 'Wheels & Tracks']],
            ['name' => 'Industrial Automation', 'icon' => 'automation', 'featured' => true,
                'description' => 'PLCs, HMIs, industrial sensors, contactors, VFDs and panel components.',
                'lms_topics' => ['plc-programming'],
                'children' => ['PLCs', 'HMIs', 'Industrial Sensors', 'Contactors & Starters', 'Variable Frequency Drives', 'Industrial Relays', 'Panel Meters', 'DIN Rail & Enclosures']],
            ['name' => 'Battery Technology', 'icon' => 'battery', 'featured' => true,
                'description' => 'Cells, packs, BMS, chargers and holders across chemistries.',
                'lms_topics' => ['battery-safety', 'bms-design'],
                'children' => ['Lithium-Ion Cells', 'LiFePO4', 'Lead Acid', 'NiMH & Alkaline', 'Battery Management Systems', 'Chargers', 'Battery Holders & Hardware']],
            ['name' => 'Power Storage', 'icon' => 'storage', 'featured' => true,
                'description' => 'Energy storage systems, inverters, UPS and grid-tie equipment.',
                'children' => ['Home Energy Storage', 'Industrial ESS', 'Inverters', 'UPS Systems', 'Supercapacitors']],
            ['name' => 'Renewable Energy', 'icon' => 'renewable',
                'description' => 'Solar panels, charge controllers, mounting and wind power.',
                'lms_topics' => ['solar-design'],
                'children' => ['Solar Panels', 'Charge Controllers', 'Solar Inverters', 'Mounting & Cabling', 'Wind Turbines']],
            ['name' => 'Power Electronics', 'icon' => 'power',
                'description' => 'Power supplies, converters, regulators and power modules.',
                'children' => ['AC-DC Power Supplies', 'DC-DC Converters', 'Voltage Regulators', 'Power Modules', 'Transformers']],
            ['name' => 'EV Components', 'icon' => 'ev',
                'description' => 'Motors, controllers, chargers and parts for electric vehicles.',
                'children' => ['EV Motors', 'Motor Controllers', 'On-Board Chargers', 'Charging Connectors', 'EV Wiring & Safety']],
            ['name' => 'AI Hardware', 'icon' => 'ai', 'featured' => true,
                'description' => 'Edge AI boards, accelerators, vision kits and development platforms.',
                'lms_topics' => ['edge-ai', 'computer-vision'],
                'children' => ['Edge AI Boards', 'AI Accelerators', 'Vision Kits', 'Voice & Audio AI', 'AI Development Kits']],
            ['name' => 'DIY & Maker Tools', 'icon' => 'maker', 'featured' => true,
                'description' => 'Kits, prototyping supplies and maker essentials for every skill level.',
                'lms_topics' => ['maker-basics'],
                'children' => ['Starter Kits', 'Breadboards & Prototyping', 'Jumper Wires & Cables', 'Educational Kits', 'Maker Accessories']],
            ['name' => 'Test & Measurement', 'icon' => 'test', 'featured' => true,
                'description' => 'Multimeters, oscilloscopes, analyzers, supplies and calibration.',
                'lms_topics' => ['instrumentation'],
                'children' => ['Multimeters', 'Oscilloscopes', 'Function Generators', 'Bench Power Supplies', 'Logic & Spectrum Analyzers', 'Clamp Meters', 'Calibration Equipment']],
            ['name' => 'Laboratory Equipment', 'icon' => 'lab',
                'description' => 'Lab instruments, microscopes, environmental chambers and consumables.',
                'children' => ['Microscopes', 'Lab Instruments', 'Environmental Chambers', 'Lab Consumables']],
            ['name' => 'Manufacturing Equipment', 'icon' => 'manufacturing',
                'description' => 'SMT, soldering and rework, CNC machines and production tooling.',
                'children' => ['Soldering & Rework', 'SMT Equipment', 'CNC Machines', 'Laser Cutters & Engravers', 'Production Tooling']],
            ['name' => 'Raw Materials', 'icon' => 'materials',
                'description' => 'Metals, plastics, composites, wires and engineering consumables.',
                'children' => ['Metals & Alloys', 'Plastics & Polymers', 'Wires & Cables', 'Adhesives & Chemicals', 'Composites']],
            ['name' => 'Mechanical Components', 'icon' => 'mechanical',
                'description' => 'Bearings, gears, belts, couplings and motion hardware.',
                'children' => ['Bearings', 'Gears & Racks', 'Belts & Pulleys', 'Couplings', 'Linear Motion', 'Springs']],
            ['name' => 'Fasteners', 'icon' => 'fasteners',
                'description' => 'Screws, bolts, nuts, standoffs, rivets and assembly hardware.',
                'children' => ['Screws & Bolts', 'Nuts & Washers', 'Standoffs & Spacers', 'Rivets & Anchors']],
            ['name' => '3D Printing', 'icon' => '3dprinting', 'featured' => true,
                'description' => 'Printers, filaments, resins, parts and scanning.',
                'lms_topics' => ['3d-design'],
                'children' => ['3D Printers', 'Filaments', 'Resins', 'Printer Parts & Upgrades', '3D Scanners']],
            ['name' => 'Drone Technology', 'icon' => 'drones', 'featured' => true,
                'description' => 'Flight controllers, ESCs, frames, propulsion and FPV.',
                'lms_topics' => ['drone-building'],
                'children' => ['Flight Controllers', 'ESCs & Motors', 'Frames', 'Propellers', 'FPV Systems', 'Drone Batteries']],
            ['name' => 'Medical Electronics', 'icon' => 'medical',
                'description' => 'Biomedical sensors, modules and healthcare device components.',
                'children' => ['Biomedical Sensors', 'Patient Monitoring Modules', 'Medical Power Supplies']],
            ['name' => 'Aerospace Electronics', 'icon' => 'aerospace',
                'description' => 'High-reliability components for aerospace and space applications.',
                'children' => ['Hi-Rel Components', 'Avionics Modules', 'CubeSat & Space']],
            ['name' => 'Safety Equipment', 'icon' => 'safety',
                'description' => 'ESD protection, PPE and electrical/workshop safety.',
                'children' => ['ESD Protection', 'PPE', 'Electrical Safety', 'Fire Safety']],
            ['name' => 'Engineering Software', 'icon' => 'software',
                'description' => 'EDA, CAD/CAM, simulation and development software licenses.',
                'children' => ['EDA & PCB Design', 'CAD/CAM', 'Simulation', 'Development Tools']],
            ['name' => 'Manufacturing Services', 'icon' => 'services',
                'description' => 'PCB fabrication, assembly, CNC machining and 3D-printing services.',
                'children' => ['PCB Fabrication', 'PCB Assembly', 'CNC Machining Services', '3D Printing Services', 'Design Services']],
        ];
    }
}
