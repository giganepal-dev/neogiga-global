<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiRoboticsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRobotTypes();
        $this->seedRobotApplications();
    }

    private function seedRobotTypes(): void
    {
        $types = [
            ['name' => 'Robotic Arm', 'slug' => 'robotic-arm', 'description' => 'Industrial and collaborative robotic arms for manufacturing, assembly, and research', 'icon' => '🦾', 'sort_order' => 1],
            ['name' => 'Autonomous Mobile Robot', 'slug' => 'amr', 'description' => 'Self-navigating robots for warehouse logistics, inspection, and delivery', 'icon' => '🤖', 'sort_order' => 2],
            ['name' => 'Drone', 'slug' => 'drone', 'description' => 'Unmanned aerial vehicles for mapping, inspection, agriculture, and delivery', 'icon' => '🛸', 'sort_order' => 3],
            ['name' => 'Humanoid Robot', 'slug' => 'humanoid', 'description' => 'Human-shaped robots for research, service, and interaction', 'icon' => '🧑', 'sort_order' => 4],
            ['name' => 'Educational Robot', 'slug' => 'educational', 'description' => 'Robots designed for STEM education and learning', 'icon' => '🎓', 'sort_order' => 5],
            ['name' => 'Service Robot', 'slug' => 'service', 'description' => 'Robots for hospitality, healthcare, and customer service', 'icon' => '🏥', 'sort_order' => 6],
            ['name' => 'Agricultural Robot', 'slug' => 'agricultural', 'description' => 'Robots for farming, crop monitoring, and harvesting', 'icon' => '🌾', 'sort_order' => 7],
            ['name' => 'Inspection Robot', 'slug' => 'inspection', 'description' => 'Robots for infrastructure inspection, maintenance, and surveying', 'icon' => '🔍', 'sort_order' => 8],
            ['name' => 'Underwater Robot', 'slug' => 'underwater', 'description' => 'ROVs and AUVs for underwater exploration and maintenance', 'icon' => '🌊', 'sort_order' => 9],
            ['name' => 'Robot Chassis', 'slug' => 'robot-chassis', 'description' => 'Base platforms and chassis for building custom robots', 'icon' => '⚙️', 'sort_order' => 10],
        ];

        foreach ($types as $type) {
            DB::table('robot_types')->updateOrInsert(
                ['slug' => $type['slug']],
                $type + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function seedRobotApplications(): void
    {
        $applications = [
            ['name' => 'Manufacturing', 'slug' => 'manufacturing', 'description' => 'Factory automation, assembly lines, quality control', 'sort_order' => 1],
            ['name' => 'Warehouse Logistics', 'slug' => 'warehouse-logistics', 'description' => 'Pick-and-pack, inventory management, goods-to-person', 'sort_order' => 2],
            ['name' => 'Agriculture', 'slug' => 'agriculture', 'description' => 'Crop monitoring, precision farming, harvesting', 'sort_order' => 3],
            ['name' => 'Healthcare', 'slug' => 'healthcare', 'description' => 'Surgical assistance, rehabilitation, patient care', 'sort_order' => 4],
            ['name' => 'Education', 'slug' => 'education', 'description' => 'STEM learning, robotics courses, research labs', 'sort_order' => 5],
            ['name' => 'Construction', 'slug' => 'construction', 'description' => 'Bricklaying, 3D printing, site inspection', 'sort_order' => 6],
            ['name' => 'Security', 'slug' => 'security', 'description' => 'Patrol, surveillance, access control', 'sort_order' => 7],
            ['name' => 'Research', 'slug' => 'research', 'description' => 'Laboratory automation, data collection, experimentation', 'sort_order' => 8],
            ['name' => 'Delivery', 'slug' => 'delivery', 'description' => 'Last-mile delivery, indoor transport, mail delivery', 'sort_order' => 9],
            ['name' => 'Entertainment', 'slug' => 'entertainment', 'description' => 'Performing arts, theme parks, interactive exhibits', 'sort_order' => 10],
            ['name' => 'Space', 'slug' => 'space', 'description' => 'Planetary exploration, satellite maintenance, orbital assembly', 'sort_order' => 11],
            ['name' => 'Mining', 'slug' => 'mining', 'description' => 'Underground exploration, mineral extraction, site surveying', 'sort_order' => 12],
        ];

        foreach ($applications as $app) {
            DB::table('robot_applications')->updateOrInsert(
                ['slug' => $app['slug']],
                $app + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
