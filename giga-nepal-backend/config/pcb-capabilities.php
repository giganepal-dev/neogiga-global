<?php

/**
 * PCB manufacturing capabilities — published at /en/capabilities.
 * These are reference specs; actual capability is confirmed during engineering review.
 */
return [
    'board_types' => [
        ['key' => 'rigid_fr4', 'label' => 'Rigid FR-4', 'description' => 'Standard FR-4 laminate, 1-64 layers. Standard Tg 130, High Tg 150/170 available.', 'layers' => '1-64'],
        ['key' => 'flex', 'label' => 'Flexible', 'description' => 'Polyimide flexible circuits, adhesive-free. 1-2 layers with stiffener options.', 'layers' => '1-2'],
        ['key' => 'rigid_flex', 'label' => 'Rigid-Flex', 'description' => 'Combined rigid and flexible sections. Multilayer by engineering review.', 'layers' => '2-16'],
        ['key' => 'aluminum', 'label' => 'Aluminum Core', 'description' => 'Metal-core PCB for thermal management. 1W thermal conductivity.', 'layers' => '1'],
        ['key' => 'copper_core', 'label' => 'Copper Core', 'description' => 'High thermal conductivity (380W) for power applications.', 'layers' => '1'],
        ['key' => 'high_frequency', 'label' => 'High-Frequency', 'description' => 'Rogers, PTFE Teflon, and ceramic substrates for RF/microwave.', 'layers' => '1-8'],
        ['key' => 'ceramic', 'label' => 'Ceramic', 'description' => 'High-temperature, high-frequency ceramic substrates.', 'layers' => '1-4'],
    ],

    'materials' => [
        ['name' => 'FR-4 Standard Tg 130', 'tg' => '130°C', 'available' => true],
        ['name' => 'FR-4 High Tg 150', 'tg' => '150°C', 'available' => true],
        ['name' => 'FR-4 High Tg 170', 'tg' => '170°C', 'available' => true],
        ['name' => 'Polyimide', 'tg' => '250°C', 'available' => true],
        ['name' => 'Rogers 4350B', 'tg' => '280°C', 'available' => true],
        ['name' => 'PTFE Teflon', 'tg' => '327°C', 'available' => true],
        ['name' => 'Aluminum (1W/mK)', 'tg' => 'N/A', 'available' => true],
        ['name' => 'Copper (380W/mK)', 'tg' => 'N/A', 'available' => true],
    ],

    'dimensions' => [
        'min_board_size_mm' => '5 × 5',
        'max_board_size_mm' => '500 × 500',
        'max_board_size_note' => 'Larger sizes available by engineering review',
        'thickness_options_mm' => ['0.4', '0.6', '0.8', '1.0', '1.2', '1.6', '2.0'],
        'thickness_tolerance' => '±10% (≥1.0mm), ±0.1mm (<1.0mm)',
        'dimension_tolerance' => '±0.15 mm (routing)',
    ],

    'copper' => [
        'outer_weights' => ['0.5 oz', '1 oz', '2 oz', '3 oz'],
        'inner_weights' => ['0.5 oz', '1 oz'],
        'min_trace_spacing' => [
            '1 oz' => ['trace_mm' => 0.10, 'spacing_mm' => 0.10, 'trace_mil' => 4, 'spacing_mil' => 4],
            '2 oz' => ['trace_mm' => 0.16, 'spacing_mm' => 0.16, 'trace_mil' => 6.5, 'spacing_mil' => 6.5],
            '3 oz' => ['trace_mm' => 0.25, 'spacing_mm' => 0.25, 'trace_mil' => 10, 'spacing_mil' => 10],
        ],
        'inner_trace_spacing' => ['trace_mm' => 0.09, 'spacing_mm' => 0.09, 'trace_mil' => 3.5, 'spacing_mil' => 3.5],
        'trace_tolerance' => '±20%',
        'plating_thickness_um' => 18,
    ],

    'surface_finishes' => [
        ['key' => 'HASL_Lead_Free', 'label' => 'Lead-free HASL', 'note' => 'Standard finish', 'available' => true],
        ['key' => 'HASL', 'label' => 'HASL (leaded)', 'note' => 'Legacy finish', 'available' => true],
        ['key' => 'ENIG', 'label' => 'ENIG', 'note' => 'Gold thickness 0.05-0.12 µm. Required for 6+ layers', 'available' => true],
        ['key' => 'OSP', 'label' => 'OSP', 'note' => 'Organic solderability preservative', 'available' => true],
        ['key' => 'Immersion_Silver', 'label' => 'Immersion Silver', 'note' => 'Good for high-frequency', 'available' => true],
        ['key' => 'Immersion_Tin', 'label' => 'Immersion Tin', 'note' => 'Flat surface, lead-free', 'available' => true],
        ['key' => 'Gold_Fingers', 'label' => 'Gold Fingers', 'note' => 'Hard gold edge connectors', 'available' => true],
    ],

    'solder_mask' => [
        'colors' => ['Green', 'Blue', 'Black', 'Red', 'White', 'Yellow'],
        'type' => 'LPI (Liquid Photo-Imageable), heat-cured',
        'thickness' => '≥10 µm',
        'bridge_min_mm' => ['green' => 0.10, 'blue' => 0.10, 'red' => 0.10, 'yellow' => 0.10, 'black' => 0.13, 'white' => 0.13],
    ],

    'silkscreen' => [
        'colors' => ['White', 'Black'],
        'min_line_width_mm' => 0.15,
        'min_text_height_mm' => 1.0,
        'character_ratio' => '1:6 minimum',
    ],

    'drilling' => [
        'min_drill_mm' => ['multilayer' => 0.15, 'single_layer' => 0.30],
        'max_drill_mm' => 6.3,
        'hole_tolerance_pth' => '+0.13 / -0.08 mm',
        'hole_tolerance_npth' => '±0.05 mm',
        'position_tolerance' => '±0.05 mm',
        'min_castellated_hole_mm' => 0.50,
        'via_types' => ['Through-hole', 'Tented', 'Plugged', 'Filled'],
        'blind_buried_vias' => 'By engineering review',
    ],

    'advanced' => [
        ['name' => 'Impedance control', 'description' => 'Controlled impedance ±10%. Available on 4+ layers.', 'available' => true],
        ['name' => 'Edge plating', 'description' => 'Copper plating on board edge. By engineering review.', 'available' => true],
        ['name' => 'Castellated holes', 'description' => 'Min 0.50 mm diameter. Plated half-holes on board edge.', 'available' => true],
        ['name' => 'HDI', 'description' => 'Microvias and high-density interconnect. By engineering review.', 'available' => true],
        ['name' => 'Countersink', 'description' => 'Countersunk holes for flush mounting.', 'available' => true],
        ['name' => 'Backdrill', 'description' => 'Removes unused via stubs. 4-32 layers, thickness ≥0.8mm.', 'available' => true],
        ['name' => 'Panelization', 'description' => 'V-score, tab routing, or mouse bites.', 'available' => true],
    ],

    'testing' => [
        ['name' => 'AOI', 'description' => 'Automated Optical Inspection. Standard on all boards.', 'default' => true],
        ['name' => 'Flying Probe', 'description' => 'Full electrical test. Recommended for prototypes and small runs.', 'default' => false],
        ['name' => 'Fixture Test', 'description' => 'Dedicated test fixture. For production volumes.', 'default' => false],
        ['name' => 'Random Test', 'description' => 'Sample-based electrical test. Free, 99% pass rate.', 'default' => true],
        ['name' => 'Impedance Test', 'description' => 'TDR coupon measurement. With impedance control boards.', 'default' => false],
    ],

    'quality' => [
        'inspection_standard' => 'IPC-A-600 Class 2 (Class 3 by request)',
        'quality_certification' => 'ISO 9001',
        'quality_rate' => '99%+ (target)',
    ],

    'lead_times' => [
        'standard' => ['min_days' => 5, 'max_days' => 15, 'description' => 'Standard fabrication'],
        'fast' => ['min_days' => 3, 'max_days' => 10, 'description' => 'Accelerated processing'],
        'express' => ['min_days' => 2, 'max_days' => 5, 'description' => 'Priority engineering and fabrication'],
    ],
];
