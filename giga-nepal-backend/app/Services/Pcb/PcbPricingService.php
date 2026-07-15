<?php

namespace App\Services\Pcb;

use App\Models\Pcb\PcbPricingTier;
use Illuminate\Support\Facades\Cache;

class PcbPricingService
{
    /**
     * Calculate estimated PCB fabrication price.
     * Returns null if no matching tier found (falls back to basic formula).
     */
    public function calculate(array $specs): array
    {
        $layers = (int) ($specs['layers'] ?? 2);
        $widthMm = (float) ($specs['width_mm'] ?? 100);
        $heightMm = (float) ($specs['height_mm'] ?? 100);
        $quantity = (int) ($specs['quantity'] ?? 5);
        $material = (string) ($specs['board_material'] ?? 'FR-4');
        $finish = (string) ($specs['surface_finish'] ?? 'HASL_Lead_Free');
        $color = (string) ($specs['solder_mask_color'] ?? 'green');
        $copperOz = (string) ($specs['outer_copper_oz'] ?? '1');
        $speed = (string) ($specs['production_speed'] ?? 'standard');
        $impedanceControl = (bool) ($specs['impedance_control'] ?? false);
        $electricalTest = (bool) ($specs['electrical_test'] ?? false);

        $areaCm2 = round(($widthMm * $heightMm) / 100, 2);

        // Try DB tier first — gracefully fall back if table doesn't exist
        try {
            $tier = PcbPricingTier::forSpecs($layers, $material, $quantity, $widthMm, $heightMm)->first();
        } catch (\Exception $e) {
            $tier = null;
        }

        if ($tier) {
            $surcharges = $tier->surcharge_rates ?? [];
            return $this->calculateFromTier($tier, $areaCm2, $layers, $quantity, $finish, $color, $copperOz, $speed, $impedanceControl, $electricalTest, $surcharges);
        }

        // Fallback: basic formula
        return $this->calculateFallback($areaCm2, $layers, $quantity, $finish, $speed);
    }

    private function calculateFromTier(
        PcbPricingTier $tier,
        float $areaCm2,
        int $layers,
        int $quantity,
        string $finish,
        string $color,
        string $copperOz,
        string $speed,
        bool $impedanceControl,
        bool $electricalTest,
        array $surcharges
    ): array {
        $basePrice = (float) $tier->base_fabrication_price;
        $perSqCm = (float) $tier->price_per_sq_cm;
        $perLayer = (float) $tier->price_per_layer;

        // Base fabrication unit price
        $fabricationUnit = max(1.0, round($basePrice + ($areaCm2 * $perSqCm) + ($layers * $perLayer), 2));

        // Quantity discount (non-linear — larger quantities = lower unit cost)
        $qtyFactor = $this->quantityFactor($quantity);
        $fabricationUnitDiscounted = round($fabricationUnit * $qtyFactor, 2);
        $fabricationTotal = round($fabricationUnitDiscounted * $quantity, 2);

        // Surcharges
        $finishMult = $surcharges['finish'][$finish] ?? ($finish === 'ENIG' ? 1.30 : 1.0);
        $colorMult = $surcharges['color'][$color] ?? ($color !== 'green' ? 1.05 : 1.0);
        $copperMult = $surcharges['copper'][$copperOz] ?? ($copperOz === '2' ? 1.20 : ($copperOz === '3' ? 1.40 : 1.0));
        $speedMult = $surcharges['speed'][$speed] ?? ($speed === 'express' ? 1.5 : ($speed === 'fast' ? 1.2 : 1.0));
        $impedanceAdd = $impedanceControl ? ($surcharges['impedance_control'] ?? 25.0) : 0;
        $testAdd = $electricalTest ? ($surcharges['electrical_test'] ?? 15.0) : 0;

        $fabricationTotal = round($fabricationTotal * $finishMult * $colorMult * $copperMult * $speedMult, 2);
        $fabricationUnitFinal = round($fabricationUnitDiscounted * $finishMult * $colorMult * $copperMult * $speedMult, 2);

        $setupFee = (float) $tier->setup_fee;
        $engineeringFee = (float) $tier->engineering_fee;
        $leadTimeDays = $tier->lead_time_days;
        if ($speed === 'express') $leadTimeDays = max(2, (int) round($leadTimeDays * 0.4));
        elseif ($speed === 'fast') $leadTimeDays = max(3, (int) round($leadTimeDays * 0.7));

        $estimatedTotal = round($fabricationTotal + $setupFee + $engineeringFee + $impedanceAdd + $testAdd, 2);

        // PCBA (assembly) pricing
        $assemblyCost = 0;
        $stencilCost = 0;
        $assemblyService = (string) ($specs['assembly_service'] ?? 'none');
        if ($assemblyService !== 'none') {
            $pcba = $this->calculateAssembly($specs, $quantity);
            $assemblyCost = $pcba['assembly_total'];
            $stencilCost = $pcba['stencil_cost'];
            $estimatedTotal += $assemblyCost + $stencilCost;
            $leadTimeDays += $pcba['assembly_lead_days'];
        }

        return [
            'tier' => $tier->label,
            'board_area_cm2' => $areaCm2,
            'layers' => $layers,
            'quantity' => $quantity,
            'fabrication_unit_price' => $fabricationUnitFinal,
            'fabrication_total' => $fabricationTotal,
            'setup_fee' => $setupFee,
            'engineering_fee' => $engineeringFee,
            'impedance_adder' => $impedanceAdd,
            'electrical_test_adder' => $testAdd,
            'assembly_cost' => $assemblyCost,
            'stencil_cost' => $stencilCost,
            'estimated_total' => $estimatedTotal,
            'lead_time_days' => $leadTimeDays,
            'currency' => 'USD',
            'assembly_service' => $assemblyService,
            'surcharges_applied' => array_filter([
                'surface_finish' => $finishMult !== 1.0 ? $finish : null,
                'color' => $colorMult !== 1.0 ? $color : null,
                'copper' => $copperMult !== 1.0 ? $copperOz.'oz' : null,
                'speed' => $speedMult !== 1.0 ? $speed : null,
                'impedance_control' => $impedanceControl,
                'electrical_test' => $electricalTest,
            ]),
            'note' => 'Estimate only. Final price confirmed after engineering review of Gerber files.',
        ];
    }

    private function calculateFallback(float $areaCm2, int $layers, int $quantity, string $finish, string $speed): array
    {
        $basePerCm2 = match (true) {
            $layers <= 1 => 0.018,
            $layers <= 2 => 0.022,
            $layers <= 4 => 0.045,
            $layers <= 6 => 0.072,
            $layers <= 8 => 0.105,
            default => 0.105 + (($layers - 8) * 0.025),
        };

        $fabricationUnit = max(1.0, round($areaCm2 * $basePerCm2, 2));
        $qtyFactor = $this->quantityFactor($quantity);
        $fabricationUnitDiscounted = round($fabricationUnit * $qtyFactor, 2);
        $fabricationTotal = round($fabricationUnitDiscounted * $quantity, 2);

        $finishMult = $finish === 'ENIG' ? 1.30 : ($finish === 'HASL' ? 0.90 : 1.0);
        $speedMult = $speed === 'express' ? 1.5 : ($speed === 'fast' ? 1.2 : 1.0);
        $fabricationTotal = round($fabricationTotal * $finishMult * $speedMult, 2);

        $setupFee = $layers <= 2 ? 8.00 : ($layers <= 4 ? 25.00 : ($layers <= 6 ? 45.00 : 80.00));
        $engineeringFee = $layers <= 2 ? 5.00 : 15.00;
        $leadTimeDays = $speed === 'express' ? 3 : ($speed === 'fast' ? 5 : 7);

        return [
            'tier' => 'Standard (estimated)',
            'board_area_cm2' => $areaCm2,
            'layers' => $layers,
            'quantity' => $quantity,
            'fabrication_unit_price' => $fabricationUnitDiscounted,
            'fabrication_total' => $fabricationTotal,
            'setup_fee' => $setupFee,
            'engineering_fee' => $engineeringFee,
            'estimated_total' => round($fabricationTotal + $setupFee + $engineeringFee, 2),
            'lead_time_days' => $leadTimeDays,
            'currency' => 'USD',
            'surcharges_applied' => [],
            'note' => 'Estimate only. Final price confirmed after engineering review of Gerber files.',
        ];
    }

    private function calculateAssembly(array $specs, int $boardQty): array
    {
        $service = (string) ($specs['assembly_service'] ?? 'smt_top');
        $smtPads = (int) ($specs['smt_pads_per_board'] ?? 50);
        $thJoints = (int) ($specs['through_hole_joints_per_board'] ?? 0);
        $stencil = (bool) ($specs['stencil_service'] ?? true);
        $bga = (bool) ($specs['bga_assembly'] ?? false);
        $coating = (bool) ($specs['conformal_coating'] ?? false);
        $sourcing = (string) ($specs['component_sourcing'] ?? 'customer_supplied');
        $testType = (string) ($specs['assembly_testing'] ?? 'visual');

        // SMT cost: $0.005/joint for standard
        $sides = match ($service) {
            'smt_both' => 2, 'mixed' => 2,
            'smt_top', 'smt_bottom', 'through_hole' => 1,
            default => 0,
        };
        $smtCost = round($smtPads * 0.005 * $sides * $boardQty, 2);

        // Through-hole cost: $0.03/joint
        $thCost = round($thJoints * 0.03 * $boardQty, 2);

        // Setup fee per side
        $setupCost = $sides > 0 ? ($sides * 10.00) : 0;

        // Stencil: $25 framed, $15 frameless
        $stencilCost = $stencil ? 25.00 : 0;

        // BGA adder: $0.01/pad surcharge
        $bgaCost = $bga ? round($smtPads * 0.005 * $boardQty, 2) : 0;

        // Conformal coating: $0.02/cm² estimate
        $coatingCost = $coating ? round(($specs['width_mm'] ?? 100) * ($specs['height_mm'] ?? 100) / 100 * 0.02 * $boardQty, 2) : 0;

        // Component sourcing: $3/part fee for NeoGiga-sourced
        $sourcingCost = $sourcing === 'neogiga_sourced' ? round(($smtPads + $thJoints) * 0.50 * $boardQty, 2)
            : ($sourcing === 'mixed' ? round(($smtPads + $thJoints) * 0.25 * $boardQty, 2) : 0);

        // Testing
        $testCost = match ($testType) {
            'aoi' => 5.00, 'xray' => 25.00, 'functional' => 50.00,
            default => 0, // visual = free
        };

        $assemblyTotal = round($smtCost + $thCost + $setupCost + $bgaCost + $coatingCost + $sourcingCost + $testCost, 2);
        $leadDays = $service !== 'none' ? ($bga ? 5 : 3) : 0;

        return [
            'assembly_total' => $assemblyTotal,
            'stencil_cost' => $stencilCost,
            'assembly_lead_days' => $leadDays,
            'breakdown' => [
                'smt_placement' => $smtCost,
                'through_hole' => $thCost,
                'setup' => $setupCost,
                'bga_adder' => $bgaCost,
                'conformal_coating' => $coatingCost,
                'component_sourcing' => $sourcingCost,
                'testing' => $testCost,
            ],
        ];
    }

    private function quantityFactor(int $quantity): float
    {
        return match (true) {
            $quantity <= 5 => 1.0,
            $quantity <= 10 => 0.85,
            $quantity <= 25 => 0.60,
            $quantity <= 50 => 0.45,
            $quantity <= 100 => 0.33,
            $quantity <= 250 => 0.25,
            $quantity <= 500 => 0.20,
            $quantity <= 1000 => 0.16,
            $quantity <= 5000 => 0.12,
            default => 0.09,
        };
    }
}
