<?php

return [
    'source' => [
        'code' => 'jlcpcb_parts_database',
        'name' => 'CDFER JLCPCB/LCSC in-stock SQLite',
        'url' => 'https://github.com/CDFER/jlcpcb-parts-database',
        'file' => env('JLCPCB_SOURCE_FILE', 'jlcpcb-components.sqlite3'),
        'page_url' => 'https://cdfer.github.io/jlcpcb-parts-database/jlcpcb-components.sqlite3',
        'downloaded_at' => env('JLCPCB_SOURCE_DOWNLOADED_AT'),
        'data_year' => (int) env('JLCPCB_SOURCE_DATA_YEAR', 2026),
        'license_note' => 'The CDFER repository is MIT licensed. That license does not independently establish rights in third-party product content. Price and supplier availability remain source-provided and require commercial verification.',
    ],

    'marketplace' => [
        'code' => 'GLOBAL',
        'currency' => 'USD',
    ],

    'pricing' => [
        'margin_numerator' => 105,
        'margin_denominator' => 100,
    ],

    'availability' => [
        'desired_quantity' => 10_000,
        'minimum_quantity' => 10_000,
        'maximum_quantity' => 10_000,
        'central_percent' => 60,
        'central_warehouse_code' => 'NG-SHENZHEN-CN',
        'regional_warehouse_codes' => [
            'NG-KATHMANDU-NP',
            'NG-NEWDELHI-IN',
            'NG-DUBAI-AE',
        ],
    ],

    'chunk_size' => (int) env('JLCPCB_COMMERCE_CHUNK_SIZE', 500),

    'backup' => [
        'root' => env('JLCPCB_BACKUP_ROOT', '/home/neogiga/backups'),
        'required_files' => [
            'database.dump',
            'storage.tar',
            'current-release.tar',
            'env.backup',
            'CURRENT_RELEASE.txt',
            'MANIFEST.txt',
        ],
    ],
];
