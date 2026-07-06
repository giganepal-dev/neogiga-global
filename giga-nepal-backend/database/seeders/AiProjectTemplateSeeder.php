<?php

namespace Database\Seeders;

use App\Models\AiPlatform\AiProjectTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AiProjectTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            AiProjectTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template + [
                    'uuid' => (string) Str::uuid(),
                    'status' => 'published',
                    'permission_scope' => 'public',
                    'source_type' => 'curated_seed',
                    'source_id' => 'ai-project-template-seed-v1',
                    'source_provenance' => [
                        'curated_by' => 'NeoGiga AI foundation',
                        'date' => '2026-07-06',
                    ],
                ],
            );
        }
    }

    private function templates(): array
    {
        return [
            $this->template('4WD Robot Car', 'robotics', 'beginner', '4-6 hours', ['4WD chassis', 'DC motors', 'motor driver', 'microcontroller', 'battery holder', 'jumper wires'], ['ultrasonic sensor', 'Bluetooth module', 'line sensors'], ['screwdriver set', 'wire cutter', 'multimeter'], ['7.4V Li-ion or 6xAA pack', 'motor current budget required'], 'Connect motors to driver outputs, driver inputs to microcontroller GPIO, and power rails through a protected battery path.', ['robotics-basics-placeholder'], ['arduino-4wd-placeholder'], ['match chassis, motor driver, controller, battery'], ['Keep wheels off the table during first power test.', 'Do not short motor power rails.']),
            $this->template('Smart Agriculture Soil Monitoring', 'iot', 'beginner', '3-5 hours', ['soil moisture sensor', 'ESP32', 'temperature humidity sensor', 'display or cloud module', 'waterproof enclosure'], ['solar charger', 'relay module', 'LoRa module'], ['multimeter', 'crimp tool'], ['5V USB or solar battery pack', 'outdoor power isolation required'], 'Sensor outputs connect to ESP32 ADC/I2C pins; outdoor wiring should be sealed and strain-relieved.', ['iot-sensors-placeholder'], ['esp32-soil-monitor-placeholder'], ['match ESP32, soil sensor, enclosure, power module'], ['Use low-voltage outdoor-safe wiring.', 'Do not connect pump mains without qualified review.']),
            $this->template('ESP32 GPS Tracker', 'embedded-systems', 'intermediate', '4-6 hours', ['ESP32 board', 'GPS module', 'LiPo battery', 'charger module', 'antenna', 'enclosure'], ['GSM/LTE module', 'microSD module'], ['soldering iron', 'multimeter'], ['3.7V LiPo with charger/protection', 'sleep current budget required'], 'GPS UART connects to ESP32 serial pins; power GPS from regulated 3.3V/5V per module datasheet.', ['gps-iot-placeholder'], ['esp32-gps-tracker-placeholder'], ['match ESP32, GPS, charger, battery'], ['Use protected LiPo cells only.', 'Do not leave charging unattended during prototype testing.']),
            $this->template('Solar Battery Backup', 'power', 'intermediate', '6-8 hours', ['solar panel', 'charge controller', 'battery', 'DC-DC converter', 'fuse', 'wiring'], ['battery monitor', 'enclosure', 'MC4 connectors'], ['multimeter', 'wire stripper', 'crimp tool'], ['Battery chemistry and controller must match', 'Fuse close to battery positive terminal'], 'Panel connects to charge controller PV input, battery to controller battery terminals, load through fused DC path.', ['solar-power-placeholder'], ['solar-backup-sizing-placeholder'], ['match panel, controller, battery, fuse, converter'], ['Battery systems can deliver dangerous current.', 'Use proper fusing and polarity checks.']),
            $this->template('LiFePO4 Battery Pack', 'battery-technology', 'advanced', '8-12 hours', ['LiFePO4 cells', 'BMS', 'bus bars', 'insulation', 'fuse', 'enclosure'], ['active balancer', 'battery monitor'], ['torque wrench', 'multimeter', 'insulated tools'], ['Cell count, BMS voltage, and charge profile must match'], 'Cells connect in series/parallel according to BMS wiring diagram; balance leads must follow exact cell order.', ['lifepo4-safety-placeholder'], ['battery-pack-checklist-placeholder'], ['match cells, BMS, fuse, charger'], ['High-current battery packs require expert review.', 'Incorrect BMS wiring can cause fire or cell damage.']),
            $this->template('Drone Starter Kit', 'drones', 'advanced', '8-14 hours', ['flight controller', 'ESCs', 'brushless motors', 'frame', 'propellers', 'LiPo battery', 'receiver'], ['GPS module', 'camera', 'telemetry radio'], ['soldering iron', 'smoke stopper', 'multimeter'], ['LiPo C-rating and ESC current must match motor load'], 'ESC signal wires connect to flight controller motor outputs; power distribution must be fused/tested before props are installed.', ['drone-basics-placeholder'], ['flight-controller-setup-placeholder'], ['match FC, ESC, motors, props, battery'], ['Remove propellers during setup.', 'Follow local drone laws and battery safety rules.']),
            $this->template('Smart Home Automation', 'iot', 'intermediate', '5-8 hours', ['ESP32 or hub', 'relay module', 'sensors', 'power supply', 'enclosure'], ['voice assistant integration', 'touch switches'], ['multimeter', 'insulated screwdriver'], ['Use certified AC-DC supply when mains is involved'], 'Low-voltage controller signals drive relay inputs; mains wiring must be isolated and professionally reviewed.', ['home-automation-placeholder'], ['mqtt-home-automation-placeholder'], ['match controller, relays, sensors, enclosure'], ['Mains electricity can kill.', 'Use certified modules and qualified electrician review.']),
            $this->template('Industrial IoT Gateway', 'industrial-iot', 'advanced', '10-16 hours', ['industrial gateway', 'RS485 module', 'isolated power supply', 'DIN rail enclosure', 'surge protection'], ['4G router', 'LoRa gateway', 'Modbus sensors'], ['multimeter', 'crimp tool', 'label printer'], ['24V industrial supply with isolation and surge protection'], 'Field bus connects through isolated RS485; gateway uplink connects via Ethernet/Wi-Fi/cellular with firewall rules.', ['industrial-iot-placeholder'], ['modbus-gateway-placeholder'], ['match gateway, RS485, power, enclosure'], ['Industrial controls require site-specific safety review.', 'Do not modify live control panels without authorization.']),
            $this->template('PLC Motor Control', 'industrial-automation', 'advanced', '10-18 hours', ['PLC', 'motor starter or VFD', 'push buttons', 'E-stop', 'overload protection', 'control power supply'], ['HMI', 'proximity sensors'], ['insulated tools', 'multimeter', 'label printer'], ['Control voltage and motor power must be isolated and protected'], 'PLC outputs drive starter/VFD control terminals; E-stop and overload must be hardwired according to safety standards.', ['plc-basics-placeholder'], ['plc-motor-ladder-placeholder'], ['match PLC, VFD/starter, E-stop, overload'], ['Motor control can be lethal.', 'Qualified industrial electrician review is mandatory.']),
            $this->template('AI Camera Vision Kit', 'ai-hardware', 'intermediate', '5-9 hours', ['AI camera module', 'single-board computer', 'camera cable', 'lighting', 'mount', 'power supply'], ['edge TPU/NPU module', 'display'], ['screwdriver', 'ESD strap'], ['Stable 5V/12V supply sized for compute load'], 'Camera connects to CSI/USB; lighting and compute board share a stable power plan with thermal clearance.', ['computer-vision-placeholder'], ['edge-vision-demo-placeholder'], ['match camera, SBC, power, mount'], ['Respect privacy and consent laws.', 'Avoid deploying surveillance without authorization.']),
        ];
    }

    private function template(string $name, string $category, string $difficulty, string $time, array $required, array $optional, array $tools, array $power, string $wiring, array $lessons, array $code, array $matching, array $safety): array
    {
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'category' => $category,
            'difficulty_level' => $difficulty,
            'estimated_build_time' => $time,
            'required_components' => $required,
            'optional_components' => $optional,
            'required_tools' => $tools,
            'battery_power_requirements' => $power,
            'wiring_overview' => $wiring,
            'lms_lesson_links' => $lessons,
            'sample_code_placeholders' => $code,
            'product_matching_placeholders' => $matching,
            'safety_notes' => $safety,
        ];
    }
}
