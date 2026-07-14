<?php

return [
    'source' => [
        'code' => 'elecforest',
        'name' => 'ElecForest',
        'url' => 'https://elecforest.com',
    ],

    'marketplace' => [
        'code' => 'GLOBAL',
        'currency' => 'USD',
    ],

    'margin_percent' => 5,

    'media' => [
        'license_note' => 'Supplier catalogue media; license was not independently verified. Original rights facts and the open legal review must be retained.',
        'approval_note' => 'Operator-directed publication risk acknowledgement; this is not evidence of ownership, redistribution rights or an independently verified license.',
    ],

    'inventory' => [
        'total_units' => 10_000,
        'central_warehouse' => [
            'code' => 'NG-SHENZHEN-CN',
            'units' => 8_000,
        ],
        'regional_warehouses' => [
            'NG-KATHMANDU-NP' => 667,
            'NG-NEWDELHI-IN' => 667,
            'NG-DUBAI-AE' => 666,
        ],
    ],

    'quarantine_skus' => [
        'NG-EF-',
    ],

    'chunk_size' => (int) env('CATALOG_RELEASE_CHUNK_SIZE', 100),

    'reports' => [
        'disk' => env('CATALOG_RELEASE_REPORT_DISK', 'local'),
        'directory' => trim((string) env('CATALOG_RELEASE_REPORT_DIRECTORY', 'catalog-releases'), '/'),
    ],
];
