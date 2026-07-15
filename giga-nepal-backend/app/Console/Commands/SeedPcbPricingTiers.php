<?php

namespace App\Console\Commands;

use App\Models\Pcb\PcbPricingTier;
use Illuminate\Console\Command;

class SeedPcbPricingTiers extends Command
{
    protected $signature = 'pcb:seed-pricing';
    protected $description = 'Seed PCB pricing tiers with industry-standard rates';

    public function handle(): int
    {
        $surcharges = [
            'finish' => ['HASL_Lead_Free' => 1.0, 'HASL' => 0.90, 'ENIG' => 1.30, 'OSP' => 1.0, 'Immersion_Silver' => 1.20, 'Immersion_Tin' => 1.15, 'Gold_Fingers' => 1.50],
            'color' => ['green' => 1.0, 'blue' => 1.05, 'black' => 1.08, 'red' => 1.05, 'white' => 1.08, 'yellow' => 1.05],
            'copper' => ['1' => 1.0, '2' => 1.20, '3' => 1.40],
            'speed' => ['standard' => 1.0, 'fast' => 1.20, 'express' => 1.50],
            'impedance_control' => 25.00,
            'electrical_test' => 15.00,
        ];

        $tiers = [
            // 1-Layer FR-4
            ['tier_key' => '1_layer_fr4', 'label' => '1-Layer FR-4', 'min_layers' => 1, 'max_layers' => 1, 'board_material' => 'FR-4', 'min_quantity' => 5, 'max_quantity' => 100000, 'min_length_mm' => 5, 'max_length_mm' => 500, 'min_width_mm' => 5, 'max_width_mm' => 500, 'base_fabrication_price' => 2.00, 'price_per_sq_cm' => 0.012, 'price_per_layer' => 0, 'engineering_fee' => 5.00, 'setup_fee' => 5.00, 'lead_time_days' => 5, 'sort_order' => 10],

            // 2-Layer FR-4
            ['tier_key' => '2_layer_fr4', 'label' => '2-Layer FR-4 Standard', 'min_layers' => 2, 'max_layers' => 2, 'board_material' => 'FR-4', 'min_quantity' => 5, 'max_quantity' => 100000, 'min_length_mm' => 5, 'max_length_mm' => 500, 'min_width_mm' => 5, 'max_width_mm' => 500, 'base_fabrication_price' => 3.00, 'price_per_sq_cm' => 0.018, 'price_per_layer' => 0, 'engineering_fee' => 5.00, 'setup_fee' => 8.00, 'lead_time_days' => 7, 'sort_order' => 20],

            // 4-Layer FR-4
            ['tier_key' => '4_layer_fr4', 'label' => '4-Layer FR-4 Standard', 'min_layers' => 3, 'max_layers' => 4, 'board_material' => 'FR-4', 'min_quantity' => 5, 'max_quantity' => 100000, 'min_length_mm' => 5, 'max_length_mm' => 500, 'min_width_mm' => 5, 'max_width_mm' => 500, 'base_fabrication_price' => 8.00, 'price_per_sq_cm' => 0.040, 'price_per_layer' => 2.00, 'engineering_fee' => 15.00, 'setup_fee' => 25.00, 'lead_time_days' => 9, 'sort_order' => 30],

            // 6-Layer FR-4
            ['tier_key' => '6_layer_fr4', 'label' => '6-Layer FR-4 Standard', 'min_layers' => 5, 'max_layers' => 6, 'board_material' => 'FR-4', 'min_quantity' => 5, 'max_quantity' => 100000, 'min_length_mm' => 5, 'max_length_mm' => 500, 'min_width_mm' => 5, 'max_width_mm' => 500, 'base_fabrication_price' => 15.00, 'price_per_sq_cm' => 0.065, 'price_per_layer' => 3.50, 'engineering_fee' => 25.00, 'setup_fee' => 45.00, 'lead_time_days' => 12, 'sort_order' => 40],

            // 8-Layer FR-4
            ['tier_key' => '8_layer_fr4', 'label' => '8-Layer FR-4 Standard', 'min_layers' => 7, 'max_layers' => 8, 'board_material' => 'FR-4', 'min_quantity' => 5, 'max_quantity' => 100000, 'min_length_mm' => 5, 'max_length_mm' => 500, 'min_width_mm' => 5, 'max_width_mm' => 500, 'base_fabrication_price' => 28.00, 'price_per_sq_cm' => 0.095, 'price_per_layer' => 5.00, 'engineering_fee' => 35.00, 'setup_fee' => 80.00, 'lead_time_days' => 15, 'sort_order' => 50],

            // 10-14 Layer FR-4 (extended range)
            ['tier_key' => '10_14_layer_fr4', 'label' => '10-14 Layer FR-4', 'min_layers' => 9, 'max_layers' => 14, 'board_material' => 'FR-4', 'min_quantity' => 5, 'max_quantity' => 50000, 'min_length_mm' => 5, 'max_length_mm' => 450, 'min_width_mm' => 5, 'max_width_mm' => 450, 'base_fabrication_price' => 45.00, 'price_per_sq_cm' => 0.12, 'price_per_layer' => 7.50, 'engineering_fee' => 50.00, 'setup_fee' => 120.00, 'lead_time_days' => 18, 'sort_order' => 60],

            // Flex PCB
            ['tier_key' => 'flex_1_2_layer', 'label' => '1-2 Layer Flex PCB', 'min_layers' => 1, 'max_layers' => 2, 'board_material' => 'Flex', 'min_quantity' => 5, 'max_quantity' => 50000, 'min_length_mm' => 5, 'max_length_mm' => 300, 'min_width_mm' => 5, 'max_width_mm' => 300, 'base_fabrication_price' => 8.00, 'price_per_sq_cm' => 0.045, 'price_per_layer' => 0, 'engineering_fee' => 20.00, 'setup_fee' => 25.00, 'lead_time_days' => 10, 'sort_order' => 70],

            // Aluminum PCB
            ['tier_key' => 'aluminum_1_layer', 'label' => '1-Layer Aluminum Core', 'min_layers' => 1, 'max_layers' => 1, 'board_material' => 'Aluminum', 'min_quantity' => 5, 'max_quantity' => 50000, 'min_length_mm' => 5, 'max_length_mm' => 400, 'min_width_mm' => 5, 'max_width_mm' => 400, 'base_fabrication_price' => 12.00, 'price_per_sq_cm' => 0.055, 'price_per_layer' => 0, 'engineering_fee' => 20.00, 'setup_fee' => 30.00, 'lead_time_days' => 10, 'sort_order' => 80],
        ];

        $count = 0;
        foreach ($tiers as $tier) {
            $tier['surcharge_rates'] = $surcharges;
            PcbPricingTier::updateOrCreate(['tier_key' => $tier['tier_key']], $tier);
            $count++;
        }

        $this->info("Seeded {$count} PCB pricing tiers.");

        return self::SUCCESS;
    }
}
