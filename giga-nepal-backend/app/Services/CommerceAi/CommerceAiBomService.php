<?php

namespace App\Services\CommerceAi;

class CommerceAiBomService
{
    public function template(string $intent): array
    {
        return match ($intent) {
            '4wd_robot_car' => [
                'title' => '4WD robot car starter BOM',
                'items' => [
                    ['name' => 'ESP32 Development Board or Arduino Uno', 'quantity' => 1, 'reason' => 'Main controller for sensors, motor commands, and wireless control.'],
                    ['name' => 'L298N Motor Driver', 'quantity' => 1, 'reason' => 'Drives four DC gear motors from the controller signals.'],
                    ['name' => '12V DC Gear Motor', 'quantity' => 4, 'reason' => 'Four-wheel drive motion.'],
                    ['name' => 'Robot Wheels', 'quantity' => 4, 'reason' => 'Wheel set matched to the gear motor shaft.'],
                    ['name' => '4WD Robot Chassis Kit', 'quantity' => 1, 'reason' => 'Mechanical frame for motors, battery, and controller.'],
                    ['name' => '18650 battery cells or 2S battery pack', 'quantity' => 1, 'reason' => 'Portable power source.'],
                    ['name' => '2S BMS Board', 'quantity' => 1, 'reason' => 'Battery protection for lithium packs.'],
                    ['name' => 'Battery charger', 'quantity' => 1, 'reason' => 'Safe charging accessory.'],
                    ['name' => 'Jumper wires', 'quantity' => 1, 'reason' => 'Prototype wiring.'],
                    ['name' => 'Switch', 'quantity' => 1, 'reason' => 'Main power control.'],
                    ['name' => 'HC-SR04 ultrasonic sensor', 'quantity' => 1, 'reason' => 'Optional obstacle sensing.'],
                    ['name' => 'Soldering iron kit', 'quantity' => 1, 'reason' => 'Assembly and repair tool.'],
                ],
            ],
            'smart_irrigation' => [
                'title' => 'Smart irrigation system BOM',
                'items' => [
                    ['name' => 'ESP32 board', 'quantity' => 1, 'reason' => 'WiFi-enabled controller.'],
                    ['name' => 'Soil moisture sensor', 'quantity' => 2, 'reason' => 'Measures soil condition.'],
                    ['name' => 'Relay module', 'quantity' => 1, 'reason' => 'Switches pump or valve load.'],
                    ['name' => 'Mini water pump', 'quantity' => 1, 'reason' => 'Moves water to the plant bed.'],
                    ['name' => 'Pipe or valve kit', 'quantity' => 1, 'reason' => 'Water delivery.'],
                    ['name' => 'Power supply', 'quantity' => 1, 'reason' => 'Stable input power.'],
                    ['name' => 'Solar panel', 'quantity' => 1, 'reason' => 'Optional remote power.'],
                    ['name' => 'Enclosure', 'quantity' => 1, 'reason' => 'Protects electronics.'],
                ],
            ],
            'school_electronics_lab' => [
                'title' => 'School electronics lab kit BOM',
                'items' => [
                    ['name' => 'Arduino Uno', 'quantity' => 10, 'reason' => 'Student-friendly microcontroller board.'],
                    ['name' => 'Breadboard', 'quantity' => 10, 'reason' => 'Solderless circuit practice.'],
                    ['name' => 'Jumper wires', 'quantity' => 10, 'reason' => 'Reusable circuit wiring.'],
                    ['name' => 'Resistor kit', 'quantity' => 5, 'reason' => 'Core passive component set.'],
                    ['name' => 'Capacitor kit', 'quantity' => 5, 'reason' => 'Timing and filtering experiments.'],
                    ['name' => 'LED kit', 'quantity' => 5, 'reason' => 'Basic output experiments.'],
                    ['name' => 'Sensor kit', 'quantity' => 5, 'reason' => 'Input and measurement projects.'],
                    ['name' => 'Multimeter', 'quantity' => 5, 'reason' => 'Measurement and debugging.'],
                    ['name' => 'Soldering kit', 'quantity' => 3, 'reason' => 'Supervised soldering practice.'],
                    ['name' => 'Storage box', 'quantity' => 10, 'reason' => 'Classroom organization.'],
                ],
            ],
            default => [
                'title' => 'Project BOM starter suggestion',
                'items' => [
                    ['name' => 'ESP32 Development Board', 'quantity' => 1, 'reason' => 'Flexible controller for IoT and robotics projects.'],
                    ['name' => 'Breadboard', 'quantity' => 1, 'reason' => 'Prototype circuit assembly.'],
                    ['name' => 'Jumper wires', 'quantity' => 1, 'reason' => 'Reusable wiring.'],
                    ['name' => 'Power supply', 'quantity' => 1, 'reason' => 'Stable bench or project power.'],
                    ['name' => 'Sensor kit', 'quantity' => 1, 'reason' => 'Common inputs for project testing.'],
                ],
            ],
        };
    }
}
