<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LmsFoundationSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('lms_courses')) {
            return;
        }
        if (DB::table('lms_courses')->count() > 1) {
            return;
        }

        $now = now();

        $courses = [
            [
                'title' => 'Electronics Component Fundamentals',
                'slug' => 'electronics-component-fundamentals',
                'description' => 'Master resistors, capacitors, inductors, diodes, and transistors.',
                'level' => 'beginner',
                'estimated_minutes' => 480,
                'status' => 'published',
                'published_at' => $now,
                'modules' => [
                    ['title' => 'Passive Components', 'slug' => 'passive-components', 'lessons' => [
                        'Resistors: Types, Values, and Power Ratings',
                        'Capacitors: Ceramic vs Electrolytic vs Tantalum',
                        'Inductors and Ferrite Beads',
                        'Reading Component Markings',
                    ]],
                    ['title' => 'Semiconductors', 'slug' => 'semiconductors', 'lessons' => [
                        'Diodes: Rectifier, Zener, Schottky, LED',
                        'BJTs and MOSFETs: Switching and Amplification',
                        'Voltage Regulators: Linear and Switching',
                    ]],
                ],
            ],
            [
                'title' => 'PCB Design with KiCad',
                'slug' => 'pcb-design-kicad',
                'description' => 'Practical PCB design from schematic to Gerber.',
                'level' => 'intermediate',
                'estimated_minutes' => 720,
                'status' => 'published',
                'published_at' => $now,
                'modules' => [
                    ['title' => 'Schematic Design', 'slug' => 'schematic-design', 'lessons' => [
                        'Creating Your First Schematic',
                        'Symbol Libraries and Component Selection',
                        'ERC and Netlist Generation',
                    ]],
                    ['title' => 'PCB Layout', 'slug' => 'pcb-layout', 'lessons' => [
                        'Board Setup and Stackup',
                        'Component Placement Strategy',
                        'Routing: Manual and Auto-router',
                        'Design Rule Check',
                    ]],
                ],
            ],
            [
                'title' => 'Microcontroller Programming with Arduino',
                'slug' => 'microcontroller-arduino',
                'description' => 'Learn embedded programming: GPIO, ADC, PWM, I2C, SPI, UART.',
                'level' => 'beginner',
                'estimated_minutes' => 600,
                'status' => 'published',
                'published_at' => $now,
                'modules' => [
                    ['title' => 'Getting Started', 'slug' => 'getting-started', 'lessons' => [
                        'Arduino IDE Setup and First Sketch',
                        'Digital I/O: LEDs, Buttons, Relays',
                        'Analog Input and PWM Output',
                    ]],
                    ['title' => 'Communication Protocols', 'slug' => 'communication-protocols', 'lessons' => [
                        'I2C: Temperature Sensors and OLED Displays',
                        'SPI: SD Cards and RF Modules',
                        'UART: GPS and Bluetooth Modules',
                        'Protocol Selection Guide',
                    ]],
                ],
            ],
        ];

        foreach ($courses as $data) {
            $modules = $data['modules'];
            unset($data['modules']);

            $courseId = DB::table('lms_courses')->insertGetId(array_merge($data, [
                'created_at' => $now, 'updated_at' => $now,
            ]));

            foreach ($modules as $mi => $mod) {
                $lessons = $mod['lessons'];
                unset($mod['lessons']);

                $moduleId = DB::table('lms_modules')->insertGetId(array_merge($mod, [
                    'lms_course_id' => $courseId,
                    'sort_order' => $mi + 1,
                    'status' => 'published',
                    'created_at' => $now, 'updated_at' => $now,
                ]));

                foreach ($lessons as $li => $title) {
                    DB::table('lms_lessons')->insert([
                        'lms_course_id' => $courseId,
                        'title' => $title,
                        'sort_order' => $li + 1,
                        'status' => 'published',
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }

        // Link courses to real products
        $productIds = DB::table('products')->whereIn('status', ['active', 'approved'])
            ->where('visibility_status', 'public')->limit(30)->pluck('id');
        $courseIds = DB::table('lms_courses')->pluck('id');

        foreach ($courseIds as $cid) {
            foreach ($productIds->random(min(5, $productIds->count())) as $pid) {
                DB::table('lms_product_links')->insert([
                    'lms_course_id' => $cid, 'product_id' => $pid, 'link_type' => 'related',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        $this->command?->info('Seeded ' . count($courses) . ' courses, ' . DB::table('lms_lessons')->count() . ' lessons, ' . DB::table('lms_product_links')->count() . ' product links.');
    }
}
