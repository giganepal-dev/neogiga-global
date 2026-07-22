<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public NeoGiga engineering taxonomy
    |--------------------------------------------------------------------------
    |
    | Only these governed domains may appear as public root categories. Supplier
    | taxonomies remain addressable for audit/import purposes, but must be mapped
    | below one of these roots before they are promoted into the directory.
    |
    */
    'root_slugs' => [
        'semiconductors',
        'electronic-components',
        'embedded-systems',
        'sensors',
        'iot-wireless',
        'robotics',
        'industrial-automation',
        'battery-technology',
        'power-storage',
        'renewable-energy',
        'power-electronics',
        'ev-components',
        'ai-hardware',
        'diy-maker-tools',
        'test-measurement',
        'laboratory-equipment',
        'manufacturing-equipment',
        'raw-materials',
        'mechanical-components',
        'fasteners',
        '3d-printing',
        'drone-technology',
        'medical-electronics',
        'aerospace-electronics',
        'safety-equipment',
        'engineering-software',
        'manufacturing-services',
    ],

    'review_slugs' => [
        '205-needs-review',
        'uncategorized',
    ],

    // Public navigation is Category → Subcategory → Child category.
    'public_depth' => 3,
];
